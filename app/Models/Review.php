<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Observers\ReviewObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ReviewObserver::class])]
class Review extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'order_item_id',
        'rating',
        'title',
        'comment',
        'images',
        'status',
        'admin_reply',
        'replied_at',
        'replied_by',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'images' => 'array',
            'is_verified_purchase' => 'boolean',
            'status' => ReviewStatus::class,
            'replied_at' => 'datetime',
            'helpful_count' => 'integer',
        ];
    }

    /**
     * is_verified_purchase is computed once, at save time, from whether
     * order_item_id points to an item of a *delivered* order - not
     * retroactively re-checked if that order's status changes afterwards
     * (out of scope; a review already reflects the state at posting time).
     */
    protected static function booted(): void
    {
        static::saving(function (Review $review) {
            if (! $review->isDirty('order_item_id') && $review->exists) {
                return;
            }

            $review->is_verified_purchase = $review->order_item_id !== null
                && OrderItem::query()
                    ->whereKey($review->order_item_id)
                    ->whereHas('order', fn (Builder $q) => $q->where('status', OrderStatus::Delivered))
                    ->exists();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'replied_by');
    }

    public function helpfulVotes(): HasMany
    {
        return $this->hasMany(ReviewHelpfulVote::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Approved);
    }
}
