<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Restricts a Coupon to specific categories/products - see the
 * migration's docblock. A coupon with no rows here is unrestricted.
 */
class Couponable extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'coupon_id',
        'couponable_type',
        'couponable_id',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function couponable(): MorphTo
    {
        return $this->morphTo();
    }
}
