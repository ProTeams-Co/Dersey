<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Shipment;

/**
 * Batch 3.4 - same orders.* permissions as OrderPolicy, not a separate
 * shipments.* resource: a shipment only exists attached to an order, and
 * from the admin's point of view "can manage this order" already covers
 * "can add/edit its shipment". No delete() - Task 4's own rule (shipment
 * deletion is explicitly forbidden), so there's no ability for a delete
 * route to check.
 */
class ShipmentPolicy
{
    public function create(Admin $actor): bool
    {
        return $actor->can('orders.update');
    }

    public function update(Admin $actor, ?Shipment $shipment = null): bool
    {
        return $actor->can('orders.update');
    }
}
