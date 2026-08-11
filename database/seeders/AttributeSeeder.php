<?php

namespace Database\Seeders;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeTranslation;
use App\Models\AttributeValue;
use App\Models\AttributeValueTranslation;
use Illuminate\Database\Seeder;

/**
 * size_letter and size_number are is_variant=true (they generate real
 * variants in Batch 2.3 - a shirt in M and a shirt in L are different
 * purchasable items). color is also is_variant=true for the same reason.
 * material and season are is_variant=false - filter-only metadata, not
 * something that splits a product into separate purchasable variants
 * (products.season already stores a product's own single season directly;
 * this attribute is the structured, filterable, multi-valued counterpart).
 */
class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->attributes() as $sort => $definition) {
            $this->createAttribute($sort, $definition);
        }
    }

    /**
     * @param  array{code: string, type: AttributeType, is_variant: bool, is_filterable: bool, name_ar: string, name_en: string, values: array<int, array>}  $definition
     */
    private function createAttribute(int $sort, array $definition): void
    {
        $attribute = Attribute::create([
            'code' => $definition['code'],
            'type' => $definition['type'],
            'is_filterable' => $definition['is_filterable'],
            'is_variant' => $definition['is_variant'],
            'is_required' => false,
            'sort' => $sort,
            'is_active' => true,
        ]);

        AttributeTranslation::create([
            'attribute_id' => $attribute->id,
            'locale' => 'ar',
            'name' => $definition['name_ar'],
        ]);

        AttributeTranslation::create([
            'attribute_id' => $attribute->id,
            'locale' => 'en',
            'name' => $definition['name_en'],
        ]);

        foreach ($definition['values'] as $valueSort => [$valueAr, $valueEn, $colorHex]) {
            $value = AttributeValue::create([
                'attribute_id' => $attribute->id,
                'color_hex' => $colorHex,
                'sort' => $valueSort,
            ]);

            AttributeValueTranslation::create([
                'attribute_value_id' => $value->id,
                'locale' => 'ar',
                'value' => $valueAr,
            ]);

            AttributeValueTranslation::create([
                'attribute_value_id' => $value->id,
                'locale' => 'en',
                'value' => $valueEn,
            ]);
        }
    }

    /**
     * @return array<int, array>
     */
    private function attributes(): array
    {
        return [
            [
                'code' => 'size_letter',
                'type' => AttributeType::Select,
                'is_variant' => true,
                'is_filterable' => true,
                'name_ar' => 'المقاس (حروف)',
                'name_en' => 'Size (Letters)',
                'values' => [
                    ['S', 'S', null],
                    ['M', 'M', null],
                    ['L', 'L', null],
                    ['XL', 'XL', null],
                    ['XXL', 'XXL', null],
                ],
            ],
            [
                'code' => 'size_number',
                'type' => AttributeType::Select,
                'is_variant' => true,
                'is_filterable' => true,
                'name_ar' => 'المقاس (أرقام)',
                'name_en' => 'Size (Numbers)',
                'values' => [
                    ['36', '36', null],
                    ['38', '38', null],
                    ['40', '40', null],
                    ['42', '42', null],
                    ['44', '44', null],
                    ['46', '46', null],
                ],
            ],
            [
                'code' => 'color',
                'type' => AttributeType::Color,
                'is_variant' => true,
                'is_filterable' => true,
                'name_ar' => 'اللون',
                'name_en' => 'Color',
                'values' => [
                    ['أحمر', 'Red', '#DC2626'],
                    ['أزرق', 'Blue', '#2563EB'],
                    ['أخضر', 'Green', '#16A34A'],
                    ['أصفر', 'Yellow', '#FACC15'],
                    ['أسود', 'Black', '#000000'],
                    ['أبيض', 'White', '#FFFFFF'],
                    ['رمادي', 'Gray', '#6B7280'],
                    ['وردي', 'Pink', '#EC4899'],
                    ['بني', 'Brown', '#92400E'],
                    ['بيج', 'Beige', '#D2B48C'],
                ],
            ],
            [
                'code' => 'material',
                'type' => AttributeType::Select,
                'is_variant' => false,
                'is_filterable' => true,
                'name_ar' => 'الخامة',
                'name_en' => 'Material',
                'values' => [
                    ['قطن', 'Cotton', null],
                    ['كتان', 'Linen', null],
                    ['حرير', 'Silk', null],
                    ['بوليستر', 'Polyester', null],
                    ['صوف', 'Wool', null],
                    ['دينيم', 'Denim', null],
                ],
            ],
            [
                'code' => 'season',
                'type' => AttributeType::Select,
                'is_variant' => false,
                'is_filterable' => true,
                'name_ar' => 'الموسم',
                'name_en' => 'Season',
                'values' => [
                    ['صيفي', 'Summer', null],
                    ['شتوي', 'Winter', null],
                    ['ربيعي', 'Spring', null],
                    ['خريفي', 'Autumn', null],
                ],
            ],
        ];
    }
}
