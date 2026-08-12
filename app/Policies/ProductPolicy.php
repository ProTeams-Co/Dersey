<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Product;

class ProductPolicy
{
    public function viewAny(Admin $actor): bool
    {
        return $actor->can('products.view');
    }

    public function view(Admin $actor, Product $product): bool
    {
        return $actor->can('products.view');
    }

    public function create(Admin $actor): bool
    {
        return $actor->can('products.create');
    }

    /**
     * $product is nullable - AdminController::bulkAction()'s own pre-check
     * (before the per-row loop) authorizes against the resource *class*,
     * not a specific row, so Laravel calls this with only $actor in that
     * case; the per-row check inside the loop passes a real instance.
     */
    public function update(Admin $actor, ?Product $product = null): bool
    {
        return $actor->can('products.update');
    }

    public function delete(Admin $actor, ?Product $product = null): bool
    {
        return $actor->can('products.delete');
    }
}
