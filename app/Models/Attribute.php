<?php

namespace App\Models;

use App\Enums\AttributeType;
use App\Support\Traits\HasTranslations;
use Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
