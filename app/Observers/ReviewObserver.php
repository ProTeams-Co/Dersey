<?php

namespace App\Observers;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;

/**
 * Keeps Product.avg_rating/reviews_count exact by recomputing from scratch
 * on every save/delete, rather than incrementing/decrementing - avoids the
 * drift an incremental running average would accumulate over many status
 * changes (pending -> approved -> rejected -> approved, edits, deletes...).
 * Only ReviewStatus::Approved rows count.
 */
class ReviewObserver
{
    public function saved(Review $review): void
    {
        $this->recalculate($review->product);
    }

    public function deleted(Review $review): void
    {
        $this->recalculate($review->product);
    }

    private function recalculate(Product $product): void
    {
        $stats = $product->reviews()
            ->where('status', ReviewStatus::Approved)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as reviews_count')
            ->first();

        // forceFill(), not update(): avg_rating/reviews_count are
        // system-computed and deliberately absent from Product::$fillable.
        $product->forceFill([
            'avg_rating' => round((float) ($stats->avg_rating ?? 0), 2),
            'reviews_count' => (int) ($stats->reviews_count ?? 0),
        ])->save();
    }
}
