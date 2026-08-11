<?php

namespace Database\Factories;

use App\Models\BrandTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandTranslation>
 *
 * brand_id must be passed explicitly by the caller (see BrandFactory) -
 * same reasoning as CategoryTranslationFactory.
 */
class BrandTranslationFactory extends Factory
{
    protected $model = BrandTranslation::class;

    public function definition(): array
    {
        return [
            'locale' => 'ar',
            'name' => fake()->unique()->company(),
            'description' => fake('ar_EG')->optional()->sentence(),
        ];
    }
}
