<?php

namespace App\Models;

use App\Models\Concerns\HasAutoSlug;

class PostCategoryTranslation extends Translation
{
    use HasAutoSlug;

    protected $fillable = [
        'post_category_id',
        'locale',
        'name',
        'slug',
    ];
}
