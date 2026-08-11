<?php

namespace Database\Factories;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->word(),
            'type' => fake()->randomElement(AttributeType::cases()),
            'is_filterable' => true,
            'is_variant' => false,
            'is_required' => false,
            'sort' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Attribute $attribute) {
            $nameAr = fake('ar_EG')->unique()->word();
            $nameEn = fake()->unique()->word();

            AttributeTranslation::factory()->create([
                'attribute_id' => $attribute->id,
                'locale' => 'ar',
                'name' => $nameAr,
            ]);

            AttributeTranslation::factory()->create([
                'attribute_id' => $attribute->id,
                'locale' => 'en',
                'name' => $nameEn,
            ]);
        });
    }

    /**
     * true = generates variants in 2.3 (size, color); false = filter-only
     * (material, season). See CLAUDE.md Batch 2.2 notes for the distinction.
     */
    public function variant(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_variant' => true,
        ]);
    }
}
