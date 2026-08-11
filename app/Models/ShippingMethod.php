<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ShippingMethodType;
use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations as HasJsonTranslations;

class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory, HasJsonTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'zone_id',
        'name',
        'description',
        'type',
        'cost',
        'free_over_amount',
        'cost_per_kg',
        'min_days',
        'max_days',
        'is_active',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'type' => ShippingMethodType::class,
            'cost' => MoneyCast::class,
            'free_over_amount' => MoneyCast::class,
            'cost_per_kg' => MoneyCast::class,
            'min_days' => 'integer',
            'max_days' => 'integer',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
