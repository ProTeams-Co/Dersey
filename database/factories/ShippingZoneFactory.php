<?php

namespace Database\Factories;

use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZone>
 */
class ShippingZoneFactory extends Factory
{
    protected $model = ShippingZone::class;

    public function definition(): array
    {
        return [
            'name' => [
                'ar' => fake('ar_EG')->unique()->city(),
                'en' => fake()->unique()->city(),
            ],
            'is_active' => true,
            'sort' => 0,
        ];
    }
}
