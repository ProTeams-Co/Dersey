<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Brand;

class BrandPolicy
{
    public function viewAny(Admin $actor): bool
    {
        return $actor->can('brands.view');
    }

    public function view(Admin $actor, Brand $brand): bool
    {
        return $actor->can('brands.view');
    }

    public function create(Admin $actor): bool
    {
        return $actor->can('brands.create');
    }

    /**
     * $brand is nullable - AdminController::bulkAction()'s own pre-check
     * (before the per-row loop) authorizes against the resource *class*,
     * not a specific row (matching viewAny/create's shape), so Laravel
     * calls this with only $actor in that case; the per-row check inside
     * the loop passes a real instance.
     */
    public function update(Admin $actor, ?Brand $brand = null): bool
    {
        return $actor->can('brands.update');
    }

    public function delete(Admin $actor, ?Brand $brand = null): bool
    {
        return $actor->can('brands.delete');
    }
}
