<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\AttributeValueTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttributeValue>
 */
class AttributeValueFactory extends Factory
{
    protected $model = AttributeValue::class;

    public function definition(): array
    {
        return [
            'attribute_id' => Attribute::factory(),
            'color_hex' => null,
            'image' => null,
            'sort' => fake()->numberBetween(0, 100),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (AttributeValue $attributeValue) {
            $valueAr = fake('ar_EG')->unique()->word();
            $valueEn = fake()->unique()->word();

            AttributeValueTranslation::factory()->create([
                'attribute_value_id' => $attributeValue->id,
                'locale' => 'ar',
                'value' => $valueAr,
            ]);

            AttributeValueTranslation::factory()->create([
                'attribute_value_id' => $attributeValue->id,
                'locale' => 'en',
                'value' => $valueEn,
            ]);
        });
    }

    public function color(string $hex): static
    {
        return $this->state(fn (array $attributes) => [
            'color_hex' => $hex,
        ]);
    }
}
