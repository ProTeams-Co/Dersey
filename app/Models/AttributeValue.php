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

    public function isUsedByVariants(): bool
    {
        return $this->variantValues()->exists();
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
        return $this->isUsedByVariants() ? ['errors.attribute_value_in_use'] : [];
    }
}
