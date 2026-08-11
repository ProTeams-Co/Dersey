<?php

namespace Database\Seeders;

use App\Enums\BannerPosition;
use App\Models\Banner;
use App\Models\BannerTranslation;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        if (Banner::query()->exists()) {
            return;
        }

        foreach ($this->banners() as $sort => $data) {
            $banner = Banner::create([
                'position' => $data['position'],
                'image' => $data['image'],
                'image_mobile' => $data['image_mobile'],
                'link' => $data['link'],
                'sort' => $sort,
                'is_active' => true,
            ]);

            BannerTranslation::create([
                'banner_id' => $banner->id,
                'locale' => 'ar',
                'title' => $data['title_ar'],
                'subtitle' => $data['subtitle_ar'],
                'button_text' => $data['button_ar'],
                'alt' => $data['alt_ar'],
            ]);

            BannerTranslation::create([
                'banner_id' => $banner->id,
                'locale' => 'en',
                'title' => $data['title_en'],
                'subtitle' => $data['subtitle_en'],
                'button_text' => $data['button_en'],
                'alt' => $data['alt_en'],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function banners(): array
    {
        return [
            [
                'position' => BannerPosition::Hero,
                'image' => 'media/banners/hero-1.jpg', 'image_mobile' => 'media/banners/hero-1-mobile.jpg',
                'link' => '/collections/summer',
                'title_ar' => 'تشكيلة الصيف وصلت', 'title_en' => 'Summer Collection Has Arrived',
                'subtitle_ar' => 'خصومات تصل لـ30%', 'subtitle_en' => 'Discounts up to 30%',
                'button_ar' => 'تسوق الآن', 'button_en' => 'Shop Now',
                'alt_ar' => 'بانر تشكيلة الصيف', 'alt_en' => 'Summer collection banner',
            ],
            [
                'position' => BannerPosition::Mid,
                'image' => 'media/banners/mid-1.jpg', 'image_mobile' => 'media/banners/mid-1-mobile.jpg',
                'link' => '/collections/men',
                'title_ar' => 'تشكيلة الرجالي الجديدة', 'title_en' => 'New Men\'s Collection',
                'subtitle_ar' => 'أحدث الموديلات', 'subtitle_en' => 'The latest styles',
                'button_ar' => 'اكتشف المزيد', 'button_en' => 'Discover More',
                'alt_ar' => 'بانر تشكيلة الرجالي', 'alt_en' => 'Men\'s collection banner',
            ],
            [
                'position' => BannerPosition::Footer,
                'image' => 'media/banners/footer-1.jpg', 'image_mobile' => 'media/banners/footer-1-mobile.jpg',
                'link' => '/newsletter',
                'title_ar' => 'اشترك في نشرتنا البريدية', 'title_en' => 'Subscribe to Our Newsletter',
                'subtitle_ar' => 'كن أول من يعرف عروضنا', 'subtitle_en' => 'Be the first to know about our offers',
                'button_ar' => 'اشترك الآن', 'button_en' => 'Subscribe Now',
                'alt_ar' => 'بانر الاشتراك بالنشرة البريدية', 'alt_en' => 'Newsletter subscription banner',
            ],
            [
                'position' => BannerPosition::Category,
                'image' => 'media/banners/category-1.jpg', 'image_mobile' => 'media/banners/category-1-mobile.jpg',
                'link' => '/categories/dresses',
                'title_ar' => 'فساتين السهرة', 'title_en' => 'Evening Dresses',
                'subtitle_ar' => 'إطلالة مميزة لكل مناسبة', 'subtitle_en' => 'A standout look for every occasion',
                'button_ar' => 'تصفح الفساتين', 'button_en' => 'Browse Dresses',
                'alt_ar' => 'بانر فساتين السهرة', 'alt_en' => 'Evening dresses banner',
            ],
        ];
    }
}
