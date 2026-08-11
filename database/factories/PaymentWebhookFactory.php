<?php

namespace Database\Factories;

use App\Models\PaymentWebhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentWebhook>
 */
class PaymentWebhookFactory extends Factory
{
    protected $model = PaymentWebhook::class;

    public function definition(): array
    {
        return [
            'event_type' => 'TRANSACTION',
            'transaction_id' => (string) fake()->unique()->numerify('##########'),
            'payload' => ['type' => 'TRANSACTION', 'obj' => ['success' => true]],
            'hmac_valid' => true,
            'processed_at' => now(),
            'error' => null,
        ];
    }
}
