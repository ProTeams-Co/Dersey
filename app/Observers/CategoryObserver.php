<?php

namespace App\Observers;

use App\Exceptions\CategoryHasDependentsException;
use App\Models\Category;
use App\Support\Cache\VersionedCache;

/**
 * Deleting a category with live children, or with products still directly
 * assigned to it, is refused outright (both approved decisions - the
 * children case chosen over kalnoy/nestedset's own default
 * cascade-soft-delete-the-whole-subtree behavior; the products case chosen
 * over auto-unlinking the pivot or auto-moving products to the parent
 * category). Delegates the actual check to Category::canBeDeleted() so the
 * admin UI's disabled-button check and this hard enforcement can never
 * drift apart from checking two different things.
 */
class CategoryObserver
{
    public function deleting(Category $category): void
    {
        if (! $category->canBeDeleted()) {
            throw new CategoryHasDependentsException($category);
        }
    }

    public function saved(Category $category): void
    {
        VersionedCache::bump('category');
    }

    public function deleted(Category $category): void
    {
        VersionedCache::bump('category');
    }
}
