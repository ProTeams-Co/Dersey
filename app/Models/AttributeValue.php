<?php

namespace App\Models;

use App\Observers\AttributeValueObserver;
use App\Support\Traits\HasTranslations;
use Database\Factories\AttributeValueFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * canBeDeleted()/deletionBlockers() + AttributeValueObserver added in
 * Batch 3.1, mirroring Category's pattern exactly - before this, a value
 * still in use by a variant could be soft-deleted with no warning at all
 * (only forceDelete() was ever blocked, at the DB FK level).
 */
#[ObservedBy([AttributeValueObserver::class])]
class AttributeValue extends Model
{
    /** @use HasFactory<AttributeValueFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = [
        'attribute_id',
        'color_hex',
        'image',
        'sort',
    ];

    protected array $translatable = ['value'];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    public function translationModel(): string
    {
        return AttributeValueTranslation::class;
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function variantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    /**
     * Batch 3.2-C Task 1 - product_images.color_value_id is
     * restrictOnDelete() (same reasoning as product_variant_values above),
     * but nothing checked it before this: a color value used ONLY by a
     * gallery image (never by any variant) passed canBeDeleted() = true,
     * so the admin screen left the delete button enabled, and the actual
     * delete hit the DB's FK constraint as a raw, uncaught QueryException
     * (500) instead of this project's normal translated 422.
     */
    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'color_value_id');
    }

    public function isUsedByVariants(): bool
    {
        return $this->variantValues()->exists();
    }

    public function isUsedByProductImages(): bool
    {
        return $this->productImages()->exists();
    }

    public function canBeDeleted(): bool
    {
        return $this->deletionBlockers() === [];
    }

    /**
     * @return list<string>
     */
    public function deletionBlockers(): array
    {
        $blockers = [];

        if ($this->isUsedByVariants()) {
            $blockers[] = 'errors.attribute_value_in_use';
        }

        if ($this->isUsedByProductImages()) {
            $blockers[] = 'errors.attribute_value_used_in_images';
        }

        return $blockers;
    }
}
