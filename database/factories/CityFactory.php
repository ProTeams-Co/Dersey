<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Governorate;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'governorate_id' => Governorate::factory(),
            'name' => [
                'ar' => FakerFactory::create('ar_EG')->city(),
                'en' => fake('en_US')->city(),
            ],
            'is_active' => true,
            'sort' => fake()->numberBetween(0, 100),
        ];
    }
}
