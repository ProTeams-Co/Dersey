<?php

namespace App\Models;

use Database\Factories\GovernorateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations as HasJsonTranslations;

/**
 * Uses spatie/laravel-translatable (JSON column), not this project's own
 * App\Support\Traits\HasTranslations (separate-table) — reference/lookup
 * data like this is never searched or indexed by name, so the simpler JSON
 * half of the hybrid approach is the right fit. Aliased on import: both
 * traits are named HasTranslations, in different namespaces, for two
 * different translation storage strategies — see CLAUDE.md.
 */
class Governorate extends Model
{
    /** @use HasFactory<GovernorateFactory> */
    use HasFactory, HasJsonTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'code',
        'shipping_zone_id',
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

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }
}
