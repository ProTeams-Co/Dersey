<?php

namespace App\Models;

use Database\Factories\AttributeTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributeTranslation extends Translation
{
    /** @use HasFactory<AttributeTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'attribute_id',
        'locale',
        'name',
        'unit',
    ];
}
