<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'order_id',
        'payment_id',
        'amount',
        'reason',
        'status',
        'processed_by',
        'paymob_refund_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'status' => RefundStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }
}
