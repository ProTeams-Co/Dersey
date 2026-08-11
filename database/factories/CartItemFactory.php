<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 *
 * Doesn't reserve stock via InventoryService - a factory-created cart item
 * is meant for isolated model/relation tests, not exercising the reserve/
 * release flow (that's CartService's job, and its own tests use it
 * directly rather than through this factory).
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        $variant = ProductVariant::factory()->create();

        return [
            'cart_id' => Cart::factory(),
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price' => $variant->final_price,
        ];
    }
}
