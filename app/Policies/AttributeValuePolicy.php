<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\AttributeValue;

/**
 * Values are managed entirely as a nested collection inside their
 * attribute's own edit page (Batch 3.1's Task 3) - there's no separate
 * "attribute values" section in the sidebar, so they share the parent
 * resource's permission namespace (attributes.*) rather than getting
 * their own.
 */
class AttributeValuePolicy
{
    public function create(Admin $actor): bool
    {
        return $actor->can('attributes.update');
    }

    public function update(Admin $actor, ?AttributeValue $value = null): bool
    {
        return $actor->can('attributes.update');
    }

    public function delete(Admin $actor, ?AttributeValue $value = null): bool
    {
        return $actor->can('attributes.update');
    }
}
