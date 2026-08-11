<?php

namespace App\Models;

use Database\Factories\PaymentWebhookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * UNIQUE(transaction_id, event_type) on the table is the real idempotency
 * guard against Paymob's duplicate webhook deliveries - see the
 * migration. This model doesn't add any dedupe logic of its own; a
 * duplicate insert attempt is meant to fail at the database level.
 */
class PaymentWebhook extends Model
{
    /** @use HasFactory<PaymentWebhookFactory> */
    use HasFactory;

    protected $fillable = [
        'event_type',
        'transaction_id',
        'payload',
        'hmac_valid',
        'processed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'hmac_valid' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
