<?php

namespace Database\Factories;

use App\Models\Governorate;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * For test isolation only — GovernorateSeeder is the source of the real 27
 * Egyptian governorates; this generates synthetic ones so tests don't need
 * to depend on that full seeder running first.
 *
 * @extends Factory<Governorate>
 */
class GovernorateFactory extends Factory
{
    protected $model = Governorate::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => [
                'ar' => FakerFactory::create('ar_EG')->city(),
                'en' => fake('en_US')->city(),
            ],
            'is_active' => true,
            'sort' => fake()->numberBetween(0, 100),
        ];
    }
}
