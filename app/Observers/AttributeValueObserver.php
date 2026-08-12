<?php

namespace App\Observers;

use App\Exceptions\AttributeValueInUseException;
use App\Models\AttributeValue;

class AttributeValueObserver
{
    /**
     * Only guards the normal (soft) delete a controller performs - the
     * pre-existing product_variant_values.attribute_value_id
     * restrictOnDelete() FK constraint (Batch 2.2/2.3) is the sole guard
     * for forceDelete(), and stays that way: isForceDeleting() skips this
     * check so a forced hard delete still fails at the DB level with a
     * QueryException, exactly as it did before this observer existed
     * (verified by tests\Feature\Inventory\AttributeValueDeleteRestrictedTest.php,
     * which broke when this check first applied unconditionally).
     */
    public function deleting(AttributeValue $value): void
    {
        if ($value->isForceDeleting()) {
            return;
        }

        if (! $value->canBeDeleted()) {
            throw new AttributeValueInUseException($value);
        }
    }
}
