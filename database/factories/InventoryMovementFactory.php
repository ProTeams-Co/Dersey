<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        $before = fake()->numberBetween(0, 100);
        $quantity = fake()->numberBetween(1, 20);

        return [
            'variant_id' => ProductVariant::factory(),
            'type' => InventoryMovementType::In,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $before + $quantity,
            'note' => null,
        ];
    }
}
