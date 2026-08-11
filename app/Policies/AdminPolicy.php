<?php

namespace App\Policies;

use App\Models\Admin;

/**
 * The first Policy in the project - resolved via Laravel's auto-discovery
 * convention (App\Policies\{Model}Policy for App\Models\{Model}), no
 * manual Gate::policy() registration needed. $actor is always resolved
 * against the `admin` guard specifically (see AdminController::authorize()
 * override) - never assume this runs against the storefront `web` guard.
 */
class AdminPolicy
{
    public function viewAny(Admin $actor): bool
    {
        return $actor->can('admins.view');
    }

    public function view(Admin $actor, Admin $target): bool
    {
        return $actor->can('admins.view');
    }

    public function create(Admin $actor): bool
    {
        return $actor->can('admins.create');
    }

    public function update(Admin $actor, Admin $target): bool
    {
        return $actor->can('admins.update');
    }

    /**
     * Blocks self-deletion outright, on top of the permission check - an
     * admin locking themselves out by deleting/deactivating their own only
     * account is a real, easy-to-hit mistake, not a hypothetical.
     */
    public function delete(Admin $actor, Admin $target): bool
    {
        return $actor->can('admins.delete') && $actor->isNot($target);
    }
}
