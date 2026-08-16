<?php

namespace App\Models;

use App\Enums\AttributeType;
use App\Observers\AttributeObserver;
use App\Support\Traits\HasTranslations;
use Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AttributeObserver added in Batch 3.1 to block changing `is_variant` once
 * any of this attribute's values are in use by a product variant - before
 * this, nothing prevented it, which would silently break
 * ProductVariantValueObserver's own is_variant check for every variant
 * already using one of those values.
 */
#[ObservedBy([AttributeObserver::class])]
class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'is_filterable',
        'is_variant',
        'is_required',
        'sort',
        'is_active',
    ];

    protected array $translatable = ['name', 'unit'];

    protected function casts(): array
    {
        return [
            'type' => AttributeType::class,
            'is_filterable' => 'boolean',
            'is_variant' => 'boolean',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function translationModel(): string
    {
        return AttributeTranslation::class;
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true);
    }

    public function scopeVariant(Builder $query): Builder
    {
        return $query->where('is_variant', true);
    }

    public function scopeColor(Builder $query): Builder
    {
        return $query->where('type', AttributeType::Color);
    }

    /**
     * Batch 3.2-C decision A - the single point in the whole project that
     * resolves "which attribute IS color", via AttributeType::Color (a
     * real, typed signal) instead of the old code === 'color' string match
     * (ProductVariant::displayImage()'s previous logic - fragile because
     * `code` is a plain admin-editable field with nothing pinning it to
     * "color", see CLAUDE.md's Batch 3.2-C notes).
     *
     * Nothing in the schema stops more than one active Color-typed
     * attribute from existing - if that ever happens, the one with the
     * lowest `sort` wins. This is an assumption this method makes, not
     * something enforced anywhere; documented here rather than silently
     * picked.
     */
    public static function colorAttribute(): ?self
    {
        return static::query()->color()->orderBy('sort')->first();
    }

    public function isUsedByVariants(): bool
    {
        return $this->values()->whereHas('variantValues')->exists();
    }
}
