<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductTranslation;
use App\Observers\Concerns\GeneratesSlugRedirect;

class ProductTranslationObserver
{
    use GeneratesSlugRedirect;

    public function updated(ProductTranslation $translation): void
    {
        $this->handleSlugChange($translation, Product::seoPath(...));
    }
}
