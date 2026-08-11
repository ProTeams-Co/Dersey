<?php

namespace App\Models;

use Database\Factories\ShippingZoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations as HasJsonTranslations;

/**
 * spatie/laravel-translatable (JSON column), same reasoning as
 * Governorate::name - reference data, never searched/indexed.
 */
class ShippingZone extends Model
{
    /** @use HasFactory<ShippingZoneFactory> */
    use HasFactory, HasJsonTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'is_active',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function governorates(): HasMany
    {
        return $this->hasMany(Governorate::class);
    }

    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class, 'zone_id');
    }
}
