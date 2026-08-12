<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Attribute;

class AttributePolicy
{
    public function viewAny(Admin $actor): bool
    {
        return $actor->can('attributes.view');
    }

    public function view(Admin $actor, Attribute $attribute): bool
    {
        return $actor->can('attributes.view');
    }

    public function create(Admin $actor): bool
    {
        return $actor->can('attributes.create');
    }

    /**
     * $attribute is nullable - see BrandPolicy::update()'s docblock for
     * why (AdminController::bulkAction()'s class-level pre-check calls
     * this with only $actor).
     */
    public function update(Admin $actor, ?Attribute $attribute = null): bool
    {
        return $actor->can('attributes.update');
    }

    public function delete(Admin $actor, ?Attribute $attribute = null): bool
    {
        return $actor->can('attributes.delete');
    }
}
