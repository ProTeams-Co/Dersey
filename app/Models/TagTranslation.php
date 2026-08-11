<?php

namespace App\Models;

use App\Models\Concerns\HasAutoSlug;

class TagTranslation extends Translation
{
    use HasAutoSlug;

    protected $fillable = [
        'tag_id',
        'locale',
        'name',
        'slug',
    ];
}
