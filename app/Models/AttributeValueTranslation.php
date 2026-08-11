<?php

namespace App\Models;

use Database\Factories\AttributeValueTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributeValueTranslation extends Translation
{
    /** @use HasFactory<AttributeValueTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'attribute_value_id',
        'locale',
        'value',
    ];
}
