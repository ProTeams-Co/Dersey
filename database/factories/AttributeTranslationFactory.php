<?php

namespace Database\Factories;

use App\Models\AttributeTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttributeTranslation>
 *
 * attribute_id must be passed explicitly by the caller (see
 * AttributeFactory) - same reasoning as CategoryTranslationFactory.
 */
class AttributeTranslationFactory extends Factory
{
    protected $model = AttributeTranslation::class;

    public function definition(): array
    {
        return [
            'locale' => 'ar',
            'name' => fake('ar_EG')->unique()->word(),
            'unit' => null,
        ];
    }
}
