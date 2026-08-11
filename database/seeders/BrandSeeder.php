<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\BrandTranslation;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->brands() as $sort => [$name, $descriptionAr, $descriptionEn, $featured]) {
            $brand = Brand::create([
                'is_active' => true,
                'is_featured' => $featured,
                'sort' => $sort,
            ]);

            BrandTranslation::create([
                'brand_id' => $brand->id,
                'locale' => 'ar',
                'name' => $name,
                'description' => $descriptionAr,
            ]);

            BrandTranslation::create([
                'brand_id' => $brand->id,
                'locale' => 'en',
                'name' => $name,
                'description' => $descriptionEn,
            ]);
        }
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string, 3: bool}>
     */
    private function brands(): array
    {
        return [
            ['Deresy Signature', 'خط الماركة الأساسي - تصاميم كلاسيكية بجودة عالية.', 'Our flagship line - classic designs, premium quality.', true],
            ['Cairo Thread', 'أزياء يومية عملية بأسعار في متناول الجميع.', 'Practical everyday wear at accessible prices.', true],
            ['Nile Couture', 'تصاميم سواريه فاخرة للمناسبات الخاصة.', 'Luxury evening wear for special occasions.', true],
            ['Delta Denim', 'متخصصة في الجينز والقطع الكاجوال.', 'Specialists in denim and casual pieces.', false],
            ['Layla Basics', 'قطع أساسية بسيطة تناسب كل الأذواق.', 'Simple wardrobe staples for every taste.', false],
            ['Sahara Kids', 'ملابس أطفال مريحة وآمنة على البشرة.', 'Comfortable, skin-safe clothing for kids.', false],
            ['Marina Sport', 'ملابس رياضية عملية لأسلوب حياة نشط.', 'Practical activewear for an active lifestyle.', false],
        ];
    }
}
