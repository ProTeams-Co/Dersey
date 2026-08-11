<?php

use App\Models\PaymentWebhook;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records the same (transaction_id, event_type) pair only once, at the database level', function () {
    $attributes = [
        'event_type' => 'TRANSACTION',
        'transaction_id' => 'TXN-DUPLICATE-TEST',
        'payload' => ['type' => 'TRANSACTION', 'obj' => ['success' => true]],
        'hmac_valid' => true,
        'processed_at' => now(),
    ];

    PaymentWebhook::create($attributes);

    // Paymob is known to redeliver the same webhook - this simulates
    // exactly that: the identical (transaction_id, event_type) pair
    // arriving a second time.
    expect(fn () => PaymentWebhook::create($attributes))
        ->toThrow(QueryException::class);

    expect(PaymentWebhook::where('transaction_id', 'TXN-DUPLICATE-TEST')->count())->toBe(1);
});

it('still allows the same transaction_id with a different event_type', function () {
    PaymentWebhook::create([
        'event_type' => 'TRANSACTION',
        'transaction_id' => 'TXN-MULTI-EVENT',
        'payload' => ['type' => 'TRANSACTION'],
        'hmac_valid' => true,
        'processed_at' => now(),
    ]);

    PaymentWebhook::create([
        'event_type' => 'REFUND',
        'transaction_id' => 'TXN-MULTI-EVENT',
        'payload' => ['type' => 'REFUND'],
        'hmac_valid' => true,
        'processed_at' => now(),
    ]);

    expect(PaymentWebhook::where('transaction_id', 'TXN-MULTI-EVENT')->count())->toBe(2);
});
