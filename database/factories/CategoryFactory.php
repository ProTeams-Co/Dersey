<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'image' => null,
            'icon' => null,
            'sort' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'is_featured' => false,
            'show_in_menu' => true,
        ];
    }

    /**
     * Every category needs both an ar and an en translation to be usable
     * through the app (withCurrentTranslation() etc.), so both are created
     * here rather than leaving callers to remember to attach them.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Category $category) {
            CategoryTranslation::factory()->create([
                'category_id' => $category->id,
                'locale' => 'ar',
                'name' => fake('ar_EG')->unique()->words(2, true),
            ]);

            CategoryTranslation::factory()->create([
                'category_id' => $category->id,
                'locale' => 'en',
                'name' => fake()->unique()->words(2, true),
            ]);
        });
    }

    public function child(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
