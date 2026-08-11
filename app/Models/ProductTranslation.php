<?php

namespace App\Models;

use App\Models\Concerns\HasAutoSlug;
use Database\Factories\ProductTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductTranslation extends Translation
{
    /** @use HasFactory<ProductTranslationFactory> */
    use HasAutoSlug, HasFactory;

    protected $fillable = [
        'product_id',
        'locale',
        'name',
        'slug',
        'short_description',
        'description',
        'material',
        'care_instructions',
    ];
}
