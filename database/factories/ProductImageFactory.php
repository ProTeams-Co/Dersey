<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'color_value_id' => null,
            'path' => 'products/'.fake()->uuid().'.jpg',
            'alt' => [
                'ar' => fake('ar_EG')->sentence(4),
                'en' => fake()->sentence(4),
            ],
            'sort' => 0,
            'is_primary' => false,
            'width' => 1200,
            'height' => 1500,
            'blurhash' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
