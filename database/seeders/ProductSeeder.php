<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * 2 products per leaf category (18 leaves from CategorySeeder's tree = 36
 * products), each placed under a gender inferred from its root category
 * (حريمي/رجالي/أطفال). Root lookup goes through raw column queries
 * (parent_id, category_translations.name) rather than $model->translations
 * or $model->children, since Model::preventLazyLoading is on outside
 * production and those are relation properties, not explicit query calls.
 *
 * Only is_variant=false attribute values (material, season) get attached
 * to product_attribute_value here - is_variant=true ones (size, color)
 * are what Batch 2.3's product_variants will be built from, not this
 * pivot; attaching them here would be scope creep into that batch.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $brands = Brand::pluck('id');
        $materialValueIds = AttributeValue::whereHas('attribute', fn ($q) => $q->where('code', 'material'))->pluck('id');
        $seasonValueIds = AttributeValue::whereHas('attribute', fn ($q) => $q->where('code', 'season'))->pluck('id');

        $leaves = Category::whereDoesntHave('children')->orderBy('_lft')->get(['id', 'parent_id']);

        $index = 0;

        foreach ($leaves as $leaf) {
            $gender = $this->genderForLeaf($leaf);

            for ($i = 0; $i < 2; $i++) {
                $index++;

                $product = Product::factory()
                    ->when($index % 7 === 0, fn ($factory) => $factory->featured())
                    ->when($index % 5 === 0, fn ($factory) => $factory->onSale())
                    ->create([
                        'gender' => $gender,
                        'brand_id' => $brands->random(),
                        'is_new' => $index % 4 === 0,
                        'primary_category_id' => $leaf->id,
                    ]);

                $product->categories()->attach($leaf->id);

                $product->attributeValues()->attach([
                    $materialValueIds->random(),
                    $seasonValueIds->random(),
                ]);
            }
        }
    }

    private function genderForLeaf(Category $leaf): Gender
    {
        $rootId = $leaf->ancestors()->whereNull('parent_id')->value('id') ?? $leaf->id;

        $rootNameEn = CategoryTranslation::query()
            ->where('category_id', $rootId)
            ->where('locale', 'en')
            ->value('name');

        return match ($rootNameEn) {
            'Women' => Gender::Women,
            'Men' => Gender::Men,
            'Kids' => Gender::Kids,
            default => Gender::Unisex,
        };
    }
}
