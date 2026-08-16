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
            // Batch 3.2-C-fix: was fake()->randomElement(AttributeType::cases())
            // - a 1-in-3 chance of Color on every single call that doesn't
            // override it, with nothing about that call site signaling the
            // risk. Since Attribute::colorAttribute() (Batch 3.2-C decision
            // A) makes `type` a real, consumed signal (not just display
            // metadata), an untyped Attribute silently becoming Color caused
            // a confirmed, reproducible test failure (ProductImagesTest.php,
            // caught across repeated `php artisan test` runs). A caller that
            // actually wants a Color-typed attribute must say so explicitly
            // via the color() state below - never left to chance.
            'type' => AttributeType::Select,
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

    /**
     * Batch 3.2-C decision A - AttributeType::Color is the single source of
     * truth for "which attribute is the color one" (replacing the old
     * code === 'color' string match). Also is_variant = true, matching the
     * real seeded color attribute (AttributeSeeder) - a color that never
     * generates variants would be an unusual, untested shape.
     */
    public function color(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AttributeType::Color,
            'is_variant' => true,
        ]);
    }
}
