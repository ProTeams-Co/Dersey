<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * No actual Paymob API calls in this batch - tables and models only. A
 * future batch's PaymobService is what actually talks to the gateway and
 * writes raw_request/raw_response here.
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gateway',
        'method',
        'amount',
        'status',
        'paymob_intention_id',
        'paymob_order_id',
        'paymob_transaction_id',
        'raw_request',
        'raw_response',
        'paid_at',
        'failed_reason',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount' => MoneyCast::class,
            'status' => PaymentStatus::class,
            'raw_request' => 'array',
            'raw_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
