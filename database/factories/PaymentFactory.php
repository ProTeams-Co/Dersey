<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'gateway' => 'paymob',
            'method' => PaymentMethod::Card,
            'amount' => fake()->numberBetween(20000, 200000),
            'status' => PaymentStatus::Paid,
            'paymob_intention_id' => (string) fake()->unique()->numerify('##########'),
            'paymob_order_id' => (string) fake()->unique()->numerify('##########'),
            'paymob_transaction_id' => (string) fake()->unique()->numerify('##########'),
            'raw_request' => null,
            'raw_response' => null,
            'paid_at' => now(),
            'failed_reason' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
            'paid_at' => null,
            'failed_reason' => 'Card declined.',
        ]);
    }
}
