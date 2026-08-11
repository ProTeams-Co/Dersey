<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * ar_EG rather than the app-wide (en_US) faker_locale — scoped to this
     * factory only, so people/address factories get realistic Egyptian
     * names without changing what fake() returns for every other factory
     * and test in the project.
     */
    protected function egyptianFaker(): \Faker\Generator
    {
        return FakerFactory::create('ar_EG');
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = $this->egyptianFaker();

        return [
            'name' => $faker->name(),
            'email' => fake()->unique()->safeEmail(),
            // Egyptian mobile format: 01[0,1,2,5] + 8 digits. unique() is
            // scoped to the 8-digit suffix, which is enough to keep the
            // full number unique across generated rows.
            'phone' => '01'.fake()->randomElement([0, 1, 2, 5]).fake()->unique()->numerify('########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'locale' => fake()->randomElement(['ar', 'en']),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
