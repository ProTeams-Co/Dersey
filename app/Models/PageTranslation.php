<?php

namespace App\Models;

use App\Models\Concerns\HasAutoSlug;

class PageTranslation extends Translation
{
    use HasAutoSlug;

    protected $fillable = [
        'page_id',
        'locale',
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
    ];

    protected function slugSourceColumn(): string
    {
        return 'title';
    }
}
