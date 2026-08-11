<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Database\Seeder;

/**
 * A genuine 3-level tree (not a flat list with a parent_id column filled
 * in as an afterthought): 3 roots (حريمي/رجالي/أطفال), each with several
 * level-2 sub-categories, each of those with 2-3 level-3 leaves - covering
 * the exact example from the batch spec (فساتين/بلوزات/بناطيل under
 * حريمي, سواريه/كاجوال under فساتين) plus realistic siblings for the
 * other two roots.
 *
 * setParentIdAttribute() (kalnoy/nestedset) positions each node in the
 * tree the moment parent_id is set on create() - no manual _lft/_rgt
 * management needed here.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->tree() as $sort => [$nameAr, $nameEn, $children]) {
            $this->createNode($nameAr, $nameEn, null, $sort, $children);
        }
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: array}>  $children
     */
    private function createNode(string $nameAr, string $nameEn, ?int $parentId, int $sort, array $children): void
    {
        $category = Category::create([
            'parent_id' => $parentId,
            'sort' => $sort,
            'is_active' => true,
            'is_featured' => $parentId === null,
            'show_in_menu' => true,
        ]);

        CategoryTranslation::create([
            'category_id' => $category->id,
            'locale' => 'ar',
            'name' => $nameAr,
        ]);

        CategoryTranslation::create([
            'category_id' => $category->id,
            'locale' => 'en',
            'name' => $nameEn,
        ]);

        foreach ($children as $childSort => [$childNameAr, $childNameEn, $grandChildren]) {
            $this->createNode($childNameAr, $childNameEn, $category->id, $childSort, $grandChildren);
        }
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: array}>
     */
    private function tree(): array
    {
        return [
            ['حريمي', 'Women', [
                ['فساتين', 'Dresses', [
                    ['سواريه', 'Evening', []],
                    ['كاجوال', 'Casual', []],
                ]],
                ['بلوزات', 'Blouses', [
                    ['كاجوال', 'Casual', []],
                    ['رسمي', 'Formal', []],
                ]],
                ['بناطيل', 'Trousers', [
                    ['جينز', 'Jeans', []],
                    ['قماش', 'Fabric', []],
                ]],
            ]],
            ['رجالي', 'Men', [
                ['قمصان', 'Shirts', [
                    ['كاجوال', 'Casual', []],
                    ['رسمي', 'Formal', []],
                ]],
                ['بناطيل', 'Trousers', [
                    ['جينز', 'Jeans', []],
                    ['قماش', 'Fabric', []],
                ]],
                ['جواكت', 'Jackets', [
                    ['شتوي', 'Winter', []],
                    ['رياضي', 'Sport', []],
                ]],
            ]],
            ['أطفال', 'Kids', [
                ['بناتي', 'Girls', [
                    ['فساتين', 'Dresses', []],
                    ['أطقم', 'Sets', []],
                ]],
                ['ولادي', 'Boys', [
                    ['قمصان', 'Shirts', []],
                    ['بناطيل', 'Trousers', []],
                ]],
                ['مواليد', 'Newborn', [
                    ['أطقم', 'Sets', []],
                    ['بيجامات', 'Pajamas', []],
                ]],
            ]],
        ];
    }
}
