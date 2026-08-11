<?php

namespace App\Models;

use App\Models\Concerns\HasAutoSlug;
use App\Observers\CategoryTranslationObserver;
use Database\Factories\CategoryTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ObservedBy([CategoryTranslationObserver::class])]
class CategoryTranslation extends Translation
{
    /** @use HasFactory<CategoryTranslationFactory> */
    use HasAutoSlug, HasFactory;

    protected $fillable = [
        'category_id',
        'locale',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
    ];
}
