<?php

namespace App\Observers;

use App\Exceptions\AttributeIsVariantLockedException;
use App\Models\Attribute;

class AttributeObserver
{
    public function updating(Attribute $attribute): void
    {
        if ($attribute->isDirty('is_variant') && $attribute->isUsedByVariants()) {
            throw new AttributeIsVariantLockedException($attribute);
        }
    }
}
