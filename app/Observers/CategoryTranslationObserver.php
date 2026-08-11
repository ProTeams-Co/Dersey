<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Observers\Concerns\GeneratesSlugRedirect;

class CategoryTranslationObserver
{
    use GeneratesSlugRedirect;

    public function updated(CategoryTranslation $translation): void
    {
        $this->handleSlugChange($translation, Category::seoPath(...));
    }
}
