<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'logo' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort' => fake()->numberBetween(0, 100),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Brand $brand) {
            // Fashion brand names are conventionally kept in Latin script
            // even on the Arabic storefront (same as real Egyptian
            // competitors) - both locales share the same brand name text,
            // only the description differs.
            $name = fake()->unique()->company();

            BrandTranslation::factory()->create([
                'brand_id' => $brand->id,
                'locale' => 'ar',
                'name' => $name,
                'description' => fake('ar_EG')->optional()->sentence(),
            ]);

            BrandTranslation::factory()->create([
                'brand_id' => $brand->id,
                'locale' => 'en',
                'name' => $name,
                'description' => fake()->optional()->sentence(),
            ]);
        });
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
