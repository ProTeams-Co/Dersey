<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE-####')),
            'type' => DiscountType::Percent,
            'value' => fake()->numberBetween(5, 30),
            'min_order_amount' => null,
            'max_discount_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'used_count' => 0,
            'first_order_only' => false,
            'starts_at' => null,
            'expires_at' => now()->addMonths(3),
            'is_active' => true,
        ];
    }

    public function fixed(int $amountInPiasters): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::Fixed,
            'value' => $amountInPiasters,
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::FreeShipping,
            'value' => 0,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => 1,
            'used_count' => 1,
        ]);
    }
}
