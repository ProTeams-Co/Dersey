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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
