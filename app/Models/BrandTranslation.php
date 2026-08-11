<?php

namespace App\Models;

use App\Models\Concerns\HasAutoSlug;
use Database\Factories\BrandTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BrandTranslation extends Translation
{
    /** @use HasFactory<BrandTranslationFactory> */
    use HasAutoSlug, HasFactory;

    protected $fillable = [
        'brand_id',
        'locale',
        'name',
        'slug',
        'description',
    ];
}
