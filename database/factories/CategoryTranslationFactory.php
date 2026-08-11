<?php

namespace Database\Factories;

use App\Models\CategoryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryTranslation>
 *
 * category_id is not defaulted here (e.g. via Category::factory()) - this
 * factory is only ever used through CategoryFactory::configure(), which
 * passes an already-created category's id explicitly for both locales.
 * slug is left out of definition() entirely so HasAutoSlug fills it from
 * name on save, same as real application code would.
 */
class CategoryTranslationFactory extends Factory
{
    protected $model = CategoryTranslation::class;

    public function definition(): array
    {
        return [
            'locale' => 'ar',
            'name' => fake('ar_EG')->unique()->words(2, true),
            'description' => fake('ar_EG')->optional()->sentence(),
            'meta_title' => null,
            'meta_description' => null,
        ];
    }
}
