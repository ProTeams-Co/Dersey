<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Observers\ProductObserver;
use App\Support\Traits\HasTranslations;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * No inventory/stock logic here at all - that is Batch 2.3
 * (product_variants, product_images, stock). This model only knows about
 * the product itself: identity, pricing, status, and catalog placement.
 */
#[ObservedBy([ProductObserver::class])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'sku',
        'base_price',
        'compare_at_price',
        'cost_price',
        'gender',
        'season',
        'status',
        'is_featured',
        'is_new',
        'published_at',
        'weight',
    ];

    protected array $translatable = ['name', 'slug', 'short_description', 'description', 'material', 'care_instructions'];

    protected function casts(): array
    {
        return [
            'base_price' => MoneyCast::class,
            'compare_at_price' => MoneyCast::class,
            'cost_price' => MoneyCast::class,
            'gender' => Gender::class,
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'published_at' => 'datetime',
            'avg_rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'sold_count' => 'integer',
            'views_count' => 'integer',
            'weight' => 'integer',
        ];
    }

    public function translationModel(): string
    {
        return ProductTranslation::class;
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_value');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('is_new', true);
    }

    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->whereHas('categories', fn (Builder $q) => $q->where('categories.id', $categoryId));
    }

    public function scopeOfGender(Builder $query, Gender $gender): Builder
    {
        return $query->where('gender', $gender);
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null
            && $this->compare_at_price->minor() > $this->base_price->minor();
    }

    protected function discountPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->isOnSale()) {
                    return null;
                }

                $compare = $this->compare_at_price->minor();
                $base = $this->base_price->minor();

                return (int) round((($compare - $base) / $compare) * 100);
            },
        );
    }

    /**
     * Reads from the already-loaded `images` relation, same reasoning as
     * ProductVariant::optionsLabel()/displayImage() - eager-load `images`
     * before calling this on more than one product.
     */
    public function primaryImage(): ?ProductImage
    {
        return $this->images->firstWhere('is_primary', true);
    }

    public function imagesForColor(int $colorValueId): Collection
    {
        return $this->images->where('color_value_id', $colorValueId)->values();
    }

    /**
     * The Cartesian product of every given attribute's values - 3 sizes ×
     * 4 colors = 12 variants, one per combination. Each new variant gets a
     * sequential SKU derived from the product's own (globally unique)
     * SKU, and its option set is written through syncOptionValues(),
     * which is what actually enforces the is_variant / no-duplicate-
     * attribute / same-attribute-set-as-siblings rules - this method
     * itself only rejects a non-variant attribute up front, before
     * generating anything, so a bad call fails immediately instead of
     * partway through.
     *
     * @param  list<int>  $attributeIds
     * @return Collection<int, ProductVariant>
     */
    public function generateVariants(array $attributeIds): Collection
    {
        $attributes = \App\Models\Attribute::with(['values' => fn ($query) => $query->orderBy('sort')])
            ->whereIn('id', $attributeIds)
            ->get();

        if ($attributes->count() !== count($attributeIds)) {
            throw new InvalidArgumentException('One or more attribute IDs do not exist.');
        }

        $nonVariant = $attributes->first(fn (\App\Models\Attribute $attribute) => ! $attribute->is_variant);

        if ($nonVariant) {
            throw new InvalidArgumentException(
                "Attribute #{$nonVariant->id} is not a variant attribute (is_variant = false)."
            );
        }

        $valueIdSets = $attributes->map(fn (\App\Models\Attribute $attribute) => $attribute->values->pluck('id')->all())->all();

        $combinations = array_reduce($valueIdSets, function (array $carry, array $valueIds) {
            $result = [];

            foreach ($carry as $combination) {
                foreach ($valueIds as $valueId) {
                    $result[] = [...$combination, $valueId];
                }
            }

            return $result;
        }, [[]]);

        $variants = new Collection();

        foreach ($combinations as $index => $combination) {
            // low_stock_threshold set explicitly, not left to the
            // migration's column default: create() only populates the
            // in-memory model with what's passed in, so a caller reading
            // $variant->low_stock_threshold right after this (without a
            // refresh()) would otherwise get null instead of 5 - caught
            // for real via a seeder that read it to size an "at
            // threshold" stock movement and silently got 0 instead.
            $variant = $this->variants()->create([
                'sku' => "{$this->sku}-".($index + 1),
                'stock_quantity' => 0,
                'low_stock_threshold' => 5,
            ]);

            $variant->syncOptionValues($combination);
            $variants->push($variant);
        }

        return $variants;
    }

    /**
     * The variant whose option set is exactly the given value IDs - not a
     * superset or subset. This is what a product detail page uses once
     * the shopper has picked one value per option (size + color, say):
     * resolve those two value IDs straight to the one purchasable variant.
     *
     * @param  list<int>  $valueIds
     */
    public function findVariantByOptions(array $valueIds): ?ProductVariant
    {
        $valueIds = array_values(array_unique($valueIds));
        $count = count($valueIds);

        return $this->variants()
            ->whereHas('attributeValues', fn (Builder $query) => $query->whereIn('attribute_values.id', $valueIds), '=', $count)
            ->with('attributeValues')
            ->get()
            ->first(fn (ProductVariant $variant) => $variant->attributeValues->count() === $count);
    }
}
