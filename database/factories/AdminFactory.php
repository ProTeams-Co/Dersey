<?php

namespace Database\Factories;

use App\Enums\AdminStatus;
use App\Models\Admin;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected static ?string $password;

    protected $model = Admin::class;

    public function definition(): array
    {
        $faker = FakerFactory::create('ar_EG');

        return [
            'name' => $faker->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => AdminStatus::Active,
        ];
    }
}
