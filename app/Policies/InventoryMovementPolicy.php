<?php

namespace App\Policies;

use App\Models\Admin;

/**
 * Batch 3.3 - a necessary companion to ProductVariantPolicy (decision 3
 * explicitly named that one), not a separate scope decision: the movement
 * log (Task 4) is a second AdminController-based screen in the very same
 * batch, and AdminController::index()'s authorize('viewAny', ...) call
 * needs SOME policy to resolve against for InventoryMovement (a different
 * model than ProductVariant) - reusing the same inventory.view permission,
 * no separate permission string invented. No update()/delete() - the log
 * is read-only in every sense (InventoryMovement itself has no
 * update/delete path at all, see the model's own docblock).
 */
class InventoryMovementPolicy
{
    public function viewAny(Admin $actor): bool
    {
        return $actor->can('inventory.view');
    }
}
