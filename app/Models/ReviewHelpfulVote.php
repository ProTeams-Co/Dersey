<?php

namespace App\Models;

use App\Observers\ReviewHelpfulVoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ReviewHelpfulVoteObserver::class])]
class ReviewHelpfulVote extends Model
{
    protected $fillable = [
        'review_id',
        'user_id',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
