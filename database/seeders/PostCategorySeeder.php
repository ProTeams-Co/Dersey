<?php

namespace Database\Seeders;

use App\Models\PostCategory;
use App\Models\PostCategoryTranslation;
use Illuminate\Database\Seeder;

class PostCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $sort => [$nameAr, $nameEn]) {
            $existing = PostCategory::whereHas(
                'translations',
                fn ($q) => $q->where('locale', 'ar')->where('name', $nameAr)
            )->first();

            if ($existing) {
                continue;
            }

            $category = PostCategory::create(['sort' => $sort]);

            PostCategoryTranslation::create([
                'post_category_id' => $category->id,
                'locale' => 'ar',
                'name' => $nameAr,
            ]);

            PostCategoryTranslation::create([
                'post_category_id' => $category->id,
                'locale' => 'en',
                'name' => $nameEn,
            ]);
        }
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function categories(): array
    {
        return [
            ['موضة', 'Fashion'],
            ['نصائح تسوق', 'Shopping Tips'],
            ['أخبار المتجر', 'Store News'],
            ['ستايل', 'Style Guides'],
        ];
    }
}
