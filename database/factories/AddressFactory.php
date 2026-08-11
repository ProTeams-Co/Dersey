<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\City;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        $faker = FakerFactory::create('ar_EG');

        // Created eagerly (not City::factory() lazily) so the address's own
        // governorate_id can be derived from the same city instead of being
        // an unrelated, independently-generated governorate - a city whose
        // governorate doesn't match the address's stated one would be
        // inconsistent test data.
        $city = City::factory()->create();

        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['المنزل', 'الشغل']),
            'full_name' => $faker->name(),
            'phone' => '01'.fake()->randomElement([0, 1, 2, 5]).fake()->unique()->numerify('########'),
            'governorate_id' => $city->governorate_id,
            'city_id' => $city->id,
            'street' => $faker->streetName(),
            'building' => (string) fake()->numberBetween(1, 200),
            'floor' => (string) fake()->numberBetween(1, 15),
            'apartment' => (string) fake()->numberBetween(1, 20),
            'landmark' => fake()->optional()->sentence(3),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
