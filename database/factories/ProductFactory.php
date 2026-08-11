<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 *
 * No stock/inventory fields here - product_variants and stock are Batch
 * 2.3. base_price/compare_at_price/cost_price are plain piaster integers;
 * MoneyCast::set() accepts a raw int on mass assignment same as it would a
 * Money instance, so no App\Support\Money construction is needed here.
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $basePrice = fake()->numberBetween(15000, 300000); // 150 - 3000 EGP

        return [
            'brand_id' => null,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-#####-???')),
            'base_price' => $basePrice,
            'compare_at_price' => null,
            'cost_price' => (int) round($basePrice * 0.55),
            'gender' => fake()->randomElement(Gender::cases()),
            'season' => fake()->optional()->randomElement(['صيفي', 'شتوي', 'ربيعي', 'خريفي']),
            'status' => ProductStatus::Published,
            'is_featured' => false,
            'is_new' => false,
            'published_at' => now(),
            'weight' => fake()->numberBetween(150, 1200),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            ProductTranslation::factory()->create([
                'product_id' => $product->id,
                'locale' => 'ar',
            ]);

            ProductTranslation::factory()->en()->create([
                'product_id' => $product->id,
            ]);
        });
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'compare_at_price' => (int) round($attributes['base_price'] * 1.3),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
