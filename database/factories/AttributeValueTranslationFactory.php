<?php

namespace Database\Factories;

use App\Models\AttributeValueTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttributeValueTranslation>
 *
 * attribute_value_id must be passed explicitly by the caller (see
 * AttributeValueFactory) - same reasoning as CategoryTranslationFactory.
 */
class AttributeValueTranslationFactory extends Factory
{
    protected $model = AttributeValueTranslation::class;

    public function definition(): array
    {
        return [
            'locale' => 'ar',
            'value' => fake('ar_EG')->unique()->word(),
        ];
    }
}
