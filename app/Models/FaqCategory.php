<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations as HasJsonTranslations;

/**
 * `name` uses spatie/laravel-translatable (JSON column) - reference/lookup
 * data, same reasoning as Governorate/City.
 */
class FaqCategory extends Model
{
    use HasJsonTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }
}
