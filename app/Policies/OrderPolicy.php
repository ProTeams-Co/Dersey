<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Order;

/**
 * Batch 3.4 - orders.* permissions were already seeded (RolePermissionSeeder's
 * RESOURCES list, since the project's foundation batch) but never had a
 * Policy checking them until now. No delete() at all - orders are never
 * deleted (CLAUDE.md: financial records, cancellation is a status), so
 * there is no ability for a delete route/button to ever check.
 */
class OrderPolicy
{
    public function viewAny(Admin $actor): bool
    {
        return $actor->can('orders.view');
    }

    public function view(Admin $actor, ?Order $order = null): bool
    {
        return $actor->can('orders.view');
    }

    /**
     * Covers every write this screen makes: the admin_note edit, a status
     * transition, and shipment create/update (ShipmentPolicy mirrors this
     * exact permission, since a shipment only ever exists attached to an
     * order the admin can already manage).
     */
    public function update(Admin $actor, ?Order $order = null): bool
    {
        return $actor->can('orders.update');
    }
}
