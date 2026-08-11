<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 *
 * Doesn't attach option values (attributeValues/variantValues) - that
 * always goes through ProductVariant::syncOptionValues() for its
 * validation, which needs a product's chosen attribute set to call
 * correctly, so it's the seeder's job (Product::generateVariants()),
 * not this factory's.
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('VAR-#####-???')),
            // fake()->optional()->unique()->ean13() looked right but
            // doesn't actually combine the two modifiers safely - it
            // either calls ean13() on a null proxy (TypeError) or sends
            // UniqueGenerator into an infinite retry loop (confirmed by
            // actually running it, not assumed). Handling "sometimes
            // null" in plain PHP instead sidesteps the interaction bug.
            'barcode' => fake()->boolean(70) ? fake()->unique()->ean13() : null,
            'price' => null,
            'compare_at_price' => null,
            'cost_price' => null,
            'stock_quantity' => fake()->numberBetween(6, 100),
            'low_stock_threshold' => 5,
            'reserved_quantity' => 0,
            'is_active' => true,
            'sort' => 0,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $attributes['low_stock_threshold'] ?? 5,
        ]);
    }
}
