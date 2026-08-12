<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Category;

class CategoryPolicy
{
    public function viewAny(Admin $actor): bool
    {
        return $actor->can('categories.view');
    }

    public function view(Admin $actor, Category $category): bool
    {
        return $actor->can('categories.view');
    }

    public function create(Admin $actor): bool
    {
        return $actor->can('categories.create');
    }

    /**
     * $category is nullable - see BrandPolicy::update()'s docblock for
     * why (AdminController::bulkAction()'s class-level pre-check calls
     * this with only $actor).
     */
    public function update(Admin $actor, ?Category $category = null): bool
    {
        return $actor->can('categories.update');
    }

    public function delete(Admin $actor, ?Category $category = null): bool
    {
        return $actor->can('categories.delete');
    }
}
