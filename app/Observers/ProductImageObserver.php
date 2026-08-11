<?php

namespace App\Observers;

use App\Models\ProductImage;

/**
 * A product can have at most one is_primary = true image. Rather than
 * rejecting a second primary flag (which would force the caller to
 * remember to unset the old one first), saving a new primary image
 * demotes any previous one automatically - same UX as "one default
 * address per user" (AddressObserver, Batch 2.1): silently swap, don't
 * reject the write.
 *
 * Runs on `saving`, before the row is written, not `saved` like
 * AddressObserver - there's no self-reference risk here (an image row
 * being saved for the first time has no id yet either way), and doing it
 * before keeps the "unset siblings" query from ever including the row
 * that's about to become primary.
 */
class ProductImageObserver
{
    public function saving(ProductImage $image): void
    {
        if (! $image->is_primary) {
            return;
        }

        ProductImage::query()
            ->where('product_id', $image->product_id)
            ->when($image->exists, fn ($query) => $query->where('id', '!=', $image->id))
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
}
