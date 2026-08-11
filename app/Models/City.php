<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations as HasJsonTranslations;

class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory, HasJsonTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'governorate_id',
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

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
