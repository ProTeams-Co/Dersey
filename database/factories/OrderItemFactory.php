<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 *
 * product_id/variant_id point at a real, still-existing catalog row by
 * default - the snapshot test explicitly deletes the product afterward to
 * prove the snapshot survives that, rather than this factory simulating
 * an already-deleted product from the start.
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $variant = ProductVariant::factory()->create();
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->numberBetween(5000, 50000);

        return [
            'order_id' => Order::factory(),
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'product_name' => ['ar' => 'منتج تجربة', 'en' => 'Test Product'],
            'variant_options' => ['ar' => 'M / أسود', 'en' => 'M / Black'],
            'sku' => $variant->sku,
            'image_path' => 'products/'.fake()->uuid().'.jpg',
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => $unitPrice * $quantity,
        ];
    }
}
