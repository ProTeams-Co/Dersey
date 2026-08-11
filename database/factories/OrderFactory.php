<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 *
 * Builds a plausible standalone order for tests that don't need the full
 * createFromCart() flow (status-transition tests, webhook tests, ...).
 * order_number still follows the real id-derived format via
 * configure()/afterCreating(), matching OrderService::generateOrderNumber()
 * exactly, so factory-made orders look identical to real ones.
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(20000, 200000);

        return [
            'order_number' => 'PENDING-'.Str::uuid(),
            'user_id' => User::factory(),
            'guest_email' => null,
            'guest_phone' => null,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'shipping_total' => 5000,
            'tax_total' => 0,
            'grand_total' => $subtotal + 5000,
            'coupon_id' => null,
            'coupon_code' => null,
            'currency' => 'EGP',
            'payment_method' => PaymentMethod::CashOnDelivery,
            'shipping_address' => [
                'full_name' => fake('ar_EG')->name(),
                'phone' => '01'.fake()->randomElement([0, 1, 2, 5]).fake()->numerify('########'),
                'alt_phone' => null,
                'governorate' => ['ar' => 'القاهرة', 'en' => 'Cairo'],
                'city' => ['ar' => 'مدينة نصر', 'en' => 'Nasr City'],
                'street' => fake('ar_EG')->streetName(),
                'building' => (string) fake()->numberBetween(1, 100),
                'floor' => (string) fake()->numberBetween(1, 10),
                'apartment' => (string) fake()->numberBetween(1, 20),
                'landmark' => null,
            ],
            'billing_address' => null,
            'shipping_method_name' => ['ar' => 'شحن عادي', 'en' => 'Standard Shipping'],
            'customer_note' => null,
            'admin_note' => null,
            'locale' => 'ar',
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'placed_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Order $order) {
            if (str_starts_with($order->order_number, 'PENDING-')) {
                $order->order_number = sprintf('ORD-%s-%06d', $order->created_at->format('Y'), $order->id);
                $order->save();
            }
        });
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'guest_email' => fake()->unique()->safeEmail(),
            'guest_phone' => '01'.fake()->randomElement([0, 1, 2, 5]).fake()->numerify('########'),
        ]);
    }

    public function withStatus(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
