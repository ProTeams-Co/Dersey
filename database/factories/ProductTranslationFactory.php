<?php

namespace Database\Factories;

use App\Models\ProductTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductTranslation>
 *
 * product_id must be passed explicitly by the caller (see ProductFactory) -
 * same reasoning as CategoryTranslationFactory. Word banks below lean
 * toward real fashion vocabulary rather than generic Faker sentences,
 * since this data seeds the FULLTEXT index and is meant to look real.
 */
class ProductTranslationFactory extends Factory
{
    protected $model = ProductTranslation::class;

    private const ITEMS_AR = ['فستان', 'بلوزة', 'قميص', 'بنطلون', 'تيشيرت', 'جاكيت', 'تنورة', 'عباية'];

    private const ITEMS_EN = ['Dress', 'Blouse', 'Shirt', 'Trousers', 'T-Shirt', 'Jacket', 'Skirt', 'Abaya'];

    private const ADJECTIVES_AR = ['كلاسيك', 'كاجوال', 'صيفي', 'شتوي', 'أنيق', 'رياضي'];

    private const ADJECTIVES_EN = ['Classic', 'Casual', 'Summer', 'Winter', 'Elegant', 'Sport'];

    public function definition(): array
    {
        return [
            'locale' => 'ar',
            'name' => fake()->randomElement(self::ITEMS_AR).' '.fake()->randomElement(self::ADJECTIVES_AR),
            'short_description' => fake('ar_EG')->sentence(6),
            'description' => fake('ar_EG')->paragraph(3),
            'material' => fake()->randomElement(['قطن', 'كتان', 'حرير', 'بوليستر', 'صوف']),
            'care_instructions' => 'يُغسل غسيل جاف فقط، لا يُكوى على درجة حرارة عالية.',
        ];
    }

    public function en(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'en',
            'name' => fake()->randomElement(self::ITEMS_EN).' '.fake()->randomElement(self::ADJECTIVES_EN),
            'short_description' => fake()->sentence(6),
            'description' => fake()->paragraph(3),
            'material' => fake()->randomElement(['Cotton', 'Linen', 'Silk', 'Polyester', 'Wool']),
            'care_instructions' => 'Dry clean only. Do not iron on high heat.',
        ]);
    }
}
