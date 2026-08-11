<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Models\Admin;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTranslation;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        if (Post::query()->exists()) {
            return;
        }

        $authorId = Admin::query()->value('id');
        $categoryIds = PostCategory::query()->pluck('id')->all();
        $tagIds = Tag::query()->pluck('id')->all();

        foreach ($this->posts() as $index => $data) {
            $post = Post::create([
                'post_category_id' => $categoryIds[$index % count($categoryIds)] ?? null,
                'author_id' => $authorId,
                'featured_image' => 'media/blog/post-'.($index + 1).'.jpg',
                'status' => $data['status'],
                'published_at' => $data['published_at'],
                'views_count' => $data['status'] === PostStatus::Published ? random_int(50, 3000) : 0,
                'reading_time' => random_int(2, 8),
                'is_featured' => $index < 2,
            ]);

            PostTranslation::create([
                'post_id' => $post->id,
                'locale' => 'ar',
                'title' => $data['title_ar'],
                'excerpt' => $data['excerpt_ar'],
                'content' => $data['content_ar'],
                'meta_title' => $data['title_ar'],
                'meta_description' => $data['excerpt_ar'],
            ]);

            PostTranslation::create([
                'post_id' => $post->id,
                'locale' => 'en',
                'title' => $data['title_en'],
                'excerpt' => $data['excerpt_en'],
                'content' => $data['content_en'],
                'meta_title' => $data['title_en'],
                'meta_description' => $data['excerpt_en'],
            ]);

            if ($tagIds !== []) {
                $selected = collect($tagIds)->shuffle()->take(random_int(2, 3))->all();
                $post->tags()->sync($selected);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function posts(): array
    {
        $now = now();

        return [
            [
                'title_ar' => '5 نصائح لاختيار الفستان المناسب', 'title_en' => '5 Tips for Choosing the Right Dress',
                'excerpt_ar' => 'دليلك السريع لاختيار الفستان المثالي لأي مناسبة.', 'excerpt_en' => 'Your quick guide to picking the perfect dress for any occasion.',
                'content_ar' => 'اختيار الفستان المناسب يعتمد على شكل الجسم والمناسبة والقماش. في هذا المقال نشاركك أهم النصائح العملية.',
                'content_en' => 'Choosing the right dress depends on body shape, occasion, and fabric. In this article we share the most practical tips.',
                'status' => PostStatus::Published, 'published_at' => $now->copy()->subDays(30),
            ],
            [
                'title_ar' => 'صيحات الموضة لصيف 2026', 'title_en' => 'Summer 2026 Fashion Trends',
                'excerpt_ar' => 'أبرز الألوان والقصات اللي هتسيطر على موضة الصيف.', 'excerpt_en' => 'The colors and cuts dominating this summer\'s fashion.',
                'content_ar' => 'موسم الصيف بيجيب معاه صيحات جديدة كل سنة. هنا هنستعرض أبرز الاتجاهات المتوقعة لصيف 2026.',
                'content_en' => 'Every summer brings new trends. Here we cover the top expected directions for summer 2026.',
                'status' => PostStatus::Published, 'published_at' => $now->copy()->subDays(20),
            ],
            [
                'title_ar' => 'كيف تعتني بملابسك القطنية', 'title_en' => 'How to Care for Your Cotton Clothes',
                'excerpt_ar' => 'خطوات بسيطة تطول عمر ملابسك القطنية.', 'excerpt_en' => 'Simple steps to extend the life of your cotton clothes.',
                'content_ar' => 'القطن قماش رقيق يحتاج عناية خاصة عند الغسيل والكي. إليك أهم الإرشادات.',
                'content_en' => 'Cotton is a delicate fabric that needs special care when washing and ironing. Here are the key guidelines.',
                'status' => PostStatus::Published, 'published_at' => $now->copy()->subDays(15),
            ],
            [
                'title_ar' => 'دليل مقاسات الملابس الرجالية', 'title_en' => 'A Guide to Men\'s Clothing Sizes',
                'excerpt_ar' => 'إزاي تختار مقاسك الصح عند التسوق أونلاين.', 'excerpt_en' => 'How to pick your correct size when shopping online.',
                'content_ar' => 'التسوق أونلاين يحتاج فهم جيد لجدول المقاسات؛ في هذا الدليل هنشرح إزاي تقيس نفسك صح.',
                'content_en' => 'Online shopping requires a good understanding of the size chart; this guide explains how to measure yourself correctly.',
                'status' => PostStatus::Published, 'published_at' => $now->copy()->subDays(10),
            ],
            [
                'title_ar' => 'أفضل إطلالات كاجوال للعمل', 'title_en' => 'Best Casual Looks for Work',
                'excerpt_ar' => 'إطلالات مريحة وأنيقة في نفس الوقت.', 'excerpt_en' => 'Comfortable yet elegant work looks.',
                'content_ar' => 'مش لازم الإطلالة الرسمية تكون مملة. هنعرض عليك أفكار كاجوال أنيقة للعمل.',
                'content_en' => 'Formal doesn\'t have to be boring. We show you elegant casual ideas for work.',
                'status' => PostStatus::Published, 'published_at' => $now->copy()->subDays(7),
            ],
            [
                'title_ar' => 'إزاي تنسق ألوان ملابس أطفالك', 'title_en' => 'How to Coordinate Your Kids\' Clothing Colors',
                'excerpt_ar' => 'نصائح عملية لتنسيق ألوان ملابس الأطفال.', 'excerpt_en' => 'Practical tips for coordinating kids\' clothing colors.',
                'content_ar' => 'تنسيق الألوان لملابس الأطفال بيخلي إطلالتهم أحلى وأكتر راحة. إليك أهم النصائح.',
                'content_en' => 'Color coordination for kids\' clothes makes their look nicer and more comfortable. Here are the top tips.',
                'status' => PostStatus::Published, 'published_at' => $now->copy()->subDays(5),
            ],
            [
                'title_ar' => 'وصلت تشكيلة الشتاء الجديدة', 'title_en' => 'The New Winter Collection Has Arrived',
                'excerpt_ar' => 'تعرف على أحدث قطع تشكيلة الشتاء في ديرسي.', 'excerpt_en' => 'Discover the latest pieces in Dersey\'s winter collection.',
                'content_ar' => 'تشكيلة الشتاء الجديدة وصلت بتصاميم دافئة وأنيقة تناسب كل الأذواق.',
                'content_en' => 'The new winter collection has arrived with warm, elegant designs to suit every taste.',
                'status' => PostStatus::Published, 'published_at' => $now->copy()->subDays(3),
            ],
            [
                'title_ar' => 'كيفية العناية بالأحذية الجلدية', 'title_en' => 'How to Care for Leather Shoes',
                'excerpt_ar' => 'خطوات بسيطة للحفاظ على أحذيتك الجلدية لفترة أطول.', 'excerpt_en' => 'Simple steps to keep your leather shoes lasting longer.',
                'content_ar' => 'العناية بالأحذية الجلدية مش معقدة لو اتبعت الخطوات الصح من التنظيف للتلميع.',
                'content_en' => 'Caring for leather shoes isn\'t complicated if you follow the right steps from cleaning to polishing.',
                'status' => PostStatus::Published, 'published_at' => $now->copy()->subDay(),
            ],
            [
                'title_ar' => 'عروض نهاية الموسم قريبًا', 'title_en' => 'End-of-Season Sale Coming Soon',
                'excerpt_ar' => 'استعد لأقوى عروض نهاية الموسم.', 'excerpt_en' => 'Get ready for our biggest end-of-season sale.',
                'content_ar' => 'عروض نهاية الموسم قربت تبدأ بخصومات تصل لنسب كبيرة على تشكيلة واسعة من المنتجات.',
                'content_en' => 'The end-of-season sale is about to start with big discounts across a wide range of products.',
                'status' => PostStatus::Scheduled, 'published_at' => $now->copy()->addDays(3),
            ],
            [
                'title_ar' => 'مسودة: مقال قيد الإعداد', 'title_en' => 'Draft: Article in Progress',
                'excerpt_ar' => 'محتوى تحت المراجعة، لسه منشورش.', 'excerpt_en' => 'Content under review, not yet published.',
                'content_ar' => 'هذا المحتوى لسه مسودة وتحت المراجعة قبل النشر.',
                'content_en' => 'This content is still a draft under review before publishing.',
                'status' => PostStatus::Draft, 'published_at' => null,
            ],
        ];
    }
}
