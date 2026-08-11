<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Traits\HasOptimisticLock;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * No cart/order logic here - reserve()/commit() against stock_quantity/
 * reserved_quantity live in App\Services\Inventory\InventoryService
 * (Batch 2.3's own scope), not on the model itself. This model owns
 * identity, pricing override, the raw stock numbers, and the
 * variant-option relationship.
 */
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, HasOptimisticLock, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'reserved_quantity',
        'image_id',
        'is_active',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class,
            'compare_at_price' => MoneyCast::class,
            'cost_price' => MoneyCast::class,
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'reserved_quantity' => 'integer',
            'is_active' => 'boolean',
            'sort' => 'integer',
            'version' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'image_id');
    }

    public function variantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class, 'variant_id');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_values',
            'variant_id',
            'attribute_value_id'
        );
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'variant_id');
    }

    /**
     * Computed, never a column - stock_quantity minus whatever's
     * currently held by active-but-unpaid carts. CLAUDE.md forbids caching
     * stock_quantity at all, and this is derived from it every time for
     * the same reason: it must always reflect the database, not a stale
     * snapshot.
     */
    protected function availableQuantity(): Attribute
    {
        return Attribute::make(get: fn () => $this->stock_quantity - $this->reserved_quantity);
    }

    public function isInStock(): bool
    {
        return $this->available_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    protected function finalPrice(): Attribute
    {
        return Attribute::make(get: fn () => $this->price ?? $this->product->base_price);
    }

    /**
     * "M / أسود" - ordered by the owning attribute's own `sort` (e.g. Size
     * before Color), not insertion order, so the same variant always
     * renders its label the same way. Reads from already-loaded relations
     * only (attributeValues.attribute, attributeValues.translations) -
     * eager-load both before calling this on more than one variant, or
     * preventLazyLoading will throw rather than silently N+1.
     */
    public function optionsLabel(?string $locale = null): string
    {
        return $this->attributeValues
            ->sortBy(fn (AttributeValue $value) => $value->attribute->sort)
            ->map(fn (AttributeValue $value) => $value->translate($locale)?->value)
            ->filter()
            ->implode(' / ');
    }

    /**
     * Variant's own image, else the first image tagged with this
     * variant's color, else the product's single primary image. Assumes
     * the seeded 'color' attribute code (AttributeSeeder, Batch 2.2) marks
     * which of a variant's attribute values is its color - there's no
     * dedicated boolean on Attribute for this, so the code string is the
     * only signal available. Reads from already-loaded relations
     * (image, attributeValues.attribute, product.images) - eager-load
     * before calling on more than one variant.
     */
    public function displayImage(): ?ProductImage
    {
        if ($this->image) {
            return $this->image;
        }

        $colorValueId = $this->attributeValues
            ->first(fn (AttributeValue $value) => $value->attribute->code === 'color')
            ?->id;

        if ($colorValueId) {
            $colorImage = $this->product->imagesForColor($colorValueId)->first();

            if ($colorImage) {
                return $colorImage;
            }
        }

        return $this->product->primaryImage();
    }

    /**
     * Replaces this variant's whole option set atomically, after
     * validating all three invariants from the batch spec:
     *   1. no two values from the same attribute (two sizes on one variant)
     *   2. every value's attribute must be is_variant = true
     *   3. every variant of the same product must share the same
     *      *set* of attributes (not necessarily the same values) - a
     *      product can't have one variant keyed by size alone and another
     *      keyed by size+color
     * See ProductVariantValueObserver for why (1) and (2) are re-checked
     * per-row too: this method is the intended entry point, but a raw
     * ->variantValues()->create() call must not be able to bypass these
     * rules just because it skipped this method.
     *
     * @param  list<int>  $attributeValueIds
     */
    public function syncOptionValues(array $attributeValueIds): void
    {
        $attributeValueIds = array_values(array_unique($attributeValueIds));

        $values = AttributeValue::with('attribute')->findMany($attributeValueIds);

        if ($values->count() !== count($attributeValueIds)) {
            throw new InvalidArgumentException('One or more attribute value IDs do not exist.');
        }

        $nonVariant = $values->first(fn (AttributeValue $value) => ! $value->attribute->is_variant);

        if ($nonVariant) {
            throw new InvalidArgumentException(
                "Attribute value #{$nonVariant->id} belongs to a non-variant attribute (is_variant = false)."
            );
        }

        $attributeIds = $values->pluck('attribute_id');

        if ($attributeIds->count() !== $attributeIds->unique()->count()) {
            throw new InvalidArgumentException('A variant cannot have two values from the same attribute.');
        }

        $thisSet = $attributeIds->unique()->sort()->values()->all();

        // Checking against a single existing sibling (not every sibling)
        // is enough: the invariant this check enforces guarantees every
        // existing sibling already shares the same attribute set, so one
        // representative is equivalent to all of them - and turns what
        // was an O(n) fetch-and-compare per variant (O(n^2) across a
        // whole product's variant generation) into O(1) per variant.
        // Caught for real: generating 50 variants for one product with
        // the naive all-siblings version took minutes; this is instant.
        $sampleSibling = $this->product->variants()
            ->where('id', '!=', $this->id ?? 0)
            ->first();

        if ($sampleSibling) {
            $siblingSet = $sampleSibling->attributeValues()
                ->pluck('attribute_id')
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($siblingSet !== $thisSet) {
                throw new InvalidArgumentException(
                    'Every variant of this product must use the same set of attributes as its siblings.'
                );
            }
        }

        $this->variantValues()->delete();

        foreach ($attributeValueIds as $valueId) {
            $this->variantValues()->create(['attribute_value_id' => $valueId]);
        }

        $this->unsetRelation('attributeValues');
    }
}
