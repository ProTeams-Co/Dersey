<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Observers\ProductObserver;
use App\Support\Traits\HasSeo;
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
use Illuminate\Support\Str;
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
    use HasFactory, HasSeo, HasTranslations, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'primary_category_id',
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

    /**
     * The single category driving the publish gate and the admin list's
     * "category" column/filter - a plain nullable column
     * (primary_category_id), not a flag on the category_product pivot
     * (MySQL can't enforce "exactly one is_primary row" without a partial
     * unique index, which it doesn't support - see the migration). The
     * primary category is ALSO always present in categories() itself;
     * App\Services\Catalog\ProductService guarantees that invariant on
     * every write, not this relation.
     */
    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
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

    /**
     * Batch 3.2-C - ordered by `sort` so the gallery renders in the order
     * the admin arranged it, not insertion order. No new migration needed:
     * the covering index(['product_id', 'sort']) already exists on
     * product_images (Batch 3.2-C's own table), added specifically for
     * this use even before this relation itself used it.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * TODO(4.x): the only place to change when real product show routes
     * exist. The SEO plan calls for a flat path keyed off the category
     * slug (/{locale}/{category-slug}/{product-slug}), not this
     * placeholder - update the return here to match, and backfill every
     * existing `redirects` row that was generated from the old convention
     * (see CLAUDE.md's "التوجيهات التلقائية" note).
     */
    public static function seoPath(string $slug, string $locale): string
    {
        return "/{$locale}/products/{$slug}";
    }

    public function defaultSeoTitle(?string $locale = null): string
    {
        return $this->translate($locale)?->name ?? '';
    }

    public function defaultSeoDescription(?string $locale = null): ?string
    {
        $translation = $this->translate($locale);
        $text = $translation?->short_description ?: $translation?->description;

        return $text ? Str::limit(strip_tags($text), 160) : null;
    }

    public function defaultSeoImage(): ?string
    {
        return $this->primaryImage()?->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function schemaMarkup(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $translation = $this->translate($locale);
        $priceMinor = $this->base_price->minor();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $translation?->name,
            'description' => $translation?->short_description ?: $translation?->description,
            'sku' => $this->sku,
            'image' => $this->primaryImage()?->path,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'EGP',
                'price' => sprintf('%d.%02d', intdiv($priceMinor, 100), $priceMinor % 100),
                'availability' => $this->variants->sum('stock_quantity') > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ];

        if ($this->reviews_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $this->avg_rating,
                'reviewCount' => $this->reviews_count,
            ];
        }

        return $schema;
    }

    /**
     * True when nothing currently blocks this product from being published -
     * lets the admin edit screen disable the publish button and list the
     * reasons up front, same UX pattern as Category::canBeDeleted().
     */
    public function canBePublished(): bool
    {
        return $this->publicationBlockers() === [];
    }

    /**
     * Translation keys (not raw text) for every reason status can't become
     * Published right now. Condition 5 (an active variant) became a real
     * check in Batch 3.2-B - condition 6 (a primary image) is still
     * deliberately ALWAYS included, since the image gallery is Batch
     * 3.2-C and doesn't exist yet. Not a bug: every product stays
     * unpublishable on condition 6 by design until that batch lands.
     *
     * SEO (condition 4) reads the raw seoMetas() row directly, NOT
     * seoMeta() - seoMeta() merges a custom row over defaultSeoTitle()/
     * defaultSeoDescription() field-by-field, which means it ALWAYS
     * returns a non-blank title (falls back to the translated name) even
     * when no custom SeoMeta row was ever saved. Checking seoMeta()->title
     * here would make this condition impossible to fail once condition 1
     * passes - caught before shipping, not after (see Batch 3.2-A
     * correction 3).
     *
     * @return list<string>
     */
    public function publicationBlockers(): array
    {
        $blockers = [];

        $missingTranslation = collect(['ar', 'en'])->contains(
            fn (string $locale) => blank($this->translate($locale)?->name) || blank($this->translate($locale)?->slug)
        );

        if ($missingTranslation) {
            $blockers[] = 'errors.product_missing_translation';
        }

        $missingDescription = collect(['ar', 'en'])->contains(
            fn (string $locale) => blank($this->translate($locale)?->description)
        );

        if ($missingDescription) {
            $blockers[] = 'errors.product_missing_description';
        }

        if ($this->primary_category_id === null) {
            $blockers[] = 'errors.product_missing_category';
        }

        $missingSeo = collect(['ar', 'en'])->contains(function (string $locale) {
            $custom = $this->seoMetas()->where('locale', $locale)->first();

            return blank($custom?->title) || blank($custom?->description);
        });

        if ($missingSeo) {
            $blockers[] = 'errors.product_missing_seo';
        }

        // Condition 5, now real (Batch 3.2-B Task 4) - at least one active
        // (non-soft-deleted, is_active = true) variant.
        if (! $this->variants()->where('is_active', true)->exists()) {
            $blockers[] = 'errors.product_missing_variant';
        }

        // Condition 6, now real (Batch 3.2-C) - at least one image flagged
        // is_primary = true. Reads the images() relation directly (not
        // primaryImage(), which returns null just as easily for "no images
        // loaded yet" as for "no primary set" - a plain ->exists() query
        // is unambiguous and doesn't depend on the relation being
        // eager-loaded).
        if (! $this->images()->where('is_primary', true)->exists()) {
            $blockers[] = 'errors.product_missing_primary_image';
        }

        return $blockers;
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

        $variants = new Collection;

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
