<?php

namespace App\Observers;

use App\Models\Review;
use App\Models\ReviewHelpfulVote;

/**
 * Review.helpful_count is recalculated exactly (COUNT of votes), not
 * incremented/decremented, for the same drift-avoidance reasoning as
 * ReviewObserver's avg_rating.
 */
class ReviewHelpfulVoteObserver
{
    public function created(ReviewHelpfulVote $vote): void
    {
        $this->recalculate($vote->review);
    }

    public function deleted(ReviewHelpfulVote $vote): void
    {
        $this->recalculate($vote->review);
    }

    private function recalculate(Review $review): void
    {
        // forceFill(), not update(): helpful_count is system-computed and
        // deliberately absent from Review::$fillable.
        $review->forceFill(['helpful_count' => $review->helpfulVotes()->count()])->save();
    }
}
