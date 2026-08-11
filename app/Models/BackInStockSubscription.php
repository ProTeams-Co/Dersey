<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Table only, per the batch spec - no notification logic yet (a future
 * batch reads notified_at and actually sends something). Given a minimal
 * model anyway, unlike other "table only" tables in this project, so a
 * later batch doesn't have to backfill one before it can use the table at
 * all.
 */
class BackInStockSubscription extends Model
{
    protected $fillable = [
        'variant_id',
        'user_id',
        'email',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
