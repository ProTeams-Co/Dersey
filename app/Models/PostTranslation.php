<?php

namespace App\Models;

use App\Models\Concerns\HasAutoSlug;

class PostTranslation extends Translation
{
    use HasAutoSlug;

    protected $fillable = [
        'post_id',
        'locale',
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
    ];

    protected function slugSourceColumn(): string
    {
        return 'title';
    }
}
