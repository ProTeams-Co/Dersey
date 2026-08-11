<?php

namespace Database\Factories;

use App\Enums\ShippingMethodType;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    protected $model = ShippingMethod::class;

    public function definition(): array
    {
        return [
            'zone_id' => ShippingZone::factory(),
            'name' => [
                'ar' => 'شحن عادي',
                'en' => 'Standard Shipping',
            ],
            'description' => null,
            'type' => ShippingMethodType::Flat,
            'cost' => fake()->numberBetween(3000, 8000), // 30-80 EGP
            'free_over_amount' => null,
            'cost_per_kg' => null,
            'min_days' => 2,
            'max_days' => 5,
            'is_active' => true,
            'sort' => 0,
        ];
    }

    public function freeOver(int $amountInPiasters): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingMethodType::FreeOver,
            'free_over_amount' => $amountInPiasters,
        ]);
    }

    public function weightBased(int $costPerKgInPiasters): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingMethodType::WeightBased,
            'cost_per_kg' => $costPerKgInPiasters,
        ]);
    }
}
