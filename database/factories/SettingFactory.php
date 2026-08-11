<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'group' => fake()->word(),
            'key' => fake()->unique()->word(),
            'value' => fake()->sentence(),
            'type' => 'string',
        ];
    }
}
