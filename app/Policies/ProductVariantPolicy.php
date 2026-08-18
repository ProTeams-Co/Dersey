<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\ProductVariant;

/**
 * Batch 3.3 - the inventory list/movement log/manual-adjustment screens
 * authorize against ProductVariant (the unit of stock), via the
 * inventory.* permissions RolePermissionSeeder already seeds (registered
 * since the project's foundation batch, unused until now). Checked: no
 * existing code authorizes against a ProductVariant instance at all
 * (Batch 3.2-B's ProductVariantsController authorizes 'update' against the
 * owning Product instead) - this is genuinely new ground, not a conflict
 * with the variant matrix screen.
 */
class ProductVariantPolicy
{
    public function viewAny(Admin $actor): bool
    {
        return $actor->can('inventory.view');
    }

    public function view(Admin $actor, ?ProductVariant $variant = null): bool
    {
        return $actor->can('inventory.view');
    }

    /**
     * Covers both the manual stock adjustment and the low_stock_threshold
     * edit - the same permission, since both are "changing something about
     * this variant's inventory record" from the admin's point of view. No
     * delete() - this screen never removes a variant (CLAUDE.md: no
     * products/variants screen in this batch's scope at all).
     */
    public function update(Admin $actor, ?ProductVariant $variant = null): bool
    {
        return $actor->can('inventory.update');
    }
}
