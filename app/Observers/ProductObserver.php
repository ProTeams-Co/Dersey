<?php

namespace App\Observers;

use App\Models\Product;
use App\Support\Cache\VersionedCache;

/**
 * A product change also bumps the "category" tag, not just "product" -
 * descendantProductsCount() and any cached category listing/facet data
 * depend on which products exist and are visible, so a category-cache
 * entry can go stale from a product-side change alone (a new product
 * added to a category, one removed, one unpublished, ...). Bumping the
 * whole "category" tag (not a specific category ID) matches the existing
 * versioned-cache design - see CacheKeys::categoryTree()/facets(), both
 * already keyed under the shared "category" tag.
 */
class ProductObserver
{
    public function saved(Product $product): void
    {
        VersionedCache::bump('product');
        VersionedCache::bump('category');
    }

    public function deleted(Product $product): void
    {
        VersionedCache::bump('product');
        VersionedCache::bump('category');
    }
}
