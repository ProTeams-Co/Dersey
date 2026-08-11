<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\TagTranslation;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->tags() as [$nameAr, $nameEn]) {
            $existing = Tag::whereHas(
                'translations',
                fn ($q) => $q->where('locale', 'ar')->where('name', $nameAr)
            )->first();

            if ($existing) {
                continue;
            }

            $tag = Tag::create();

            TagTranslation::create(['tag_id' => $tag->id, 'locale' => 'ar', 'name' => $nameAr]);
            TagTranslation::create(['tag_id' => $tag->id, 'locale' => 'en', 'name' => $nameEn]);
        }
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function tags(): array
    {
        return [
            ['صيف', 'Summer'],
            ['شتاء', 'Winter'],
            ['حريمي', 'Women'],
            ['رجالي', 'Men'],
            ['أطفال', 'Kids'],
            ['عروض', 'Offers'],
            ['جديد', 'New Arrivals'],
            ['كاجوال', 'Casual'],
        ];
    }
}
