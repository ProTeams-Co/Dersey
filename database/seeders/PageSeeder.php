<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Database\Seeder;

/**
 * The 6 pages Paymob requires to exist (with real, reachable URLs) before
 * approving a merchant account. Content here is deliberately generic
 * placeholder text, clearly marked as such - it must be replaced with
 * reviewed legal copy before going live, not treated as final.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $sort => $page) {
            $existing = Page::whereHas(
                'translations',
                fn ($q) => $q->where('locale', 'ar')->where('slug', $page['slug_ar'])
            )->first();

            if ($existing) {
                continue;
            }

            $model = Page::create([
                'template' => $page['template'],
                'is_active' => true,
                'show_in_footer' => true,
                'sort' => $sort,
            ]);

            PageTranslation::create([
                'page_id' => $model->id,
                'locale' => 'ar',
                'title' => $page['title_ar'],
                'slug' => $page['slug_ar'],
                'content' => $page['content_ar'],
                'meta_title' => $page['title_ar'],
                'meta_description' => $page['meta_ar'],
            ]);

            PageTranslation::create([
                'page_id' => $model->id,
                'locale' => 'en',
                'title' => $page['title_en'],
                'slug' => $page['slug_en'],
                'content' => $page['content_en'],
                'meta_title' => $page['title_en'],
                'meta_description' => $page['meta_en'],
            ]);
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private function pages(): array
    {
        $legalNoticeAr = '⚠️ محتوى مبدئي - يحتاج مراجعة قانونية قبل النشر الفعلي.';
        $legalNoticeEn = '⚠️ Placeholder content - requires legal review before going live.';

        return [
            [
                'template' => 'about',
                'slug_ar' => 'من-نحن', 'slug_en' => 'about-us',
                'title_ar' => 'من نحن', 'title_en' => 'About Us',
                'meta_ar' => 'تعرف على قصة ديرسي ورؤيتنا في عالم الأزياء.',
                'meta_en' => 'Learn about Dersey\'s story and vision in fashion.',
                'content_ar' => "{$legalNoticeAr}\n\nديرسي متجر أزياء إلكتروني مصري يقدم أحدث صيحات الموضة للرجال والنساء والأطفال بجودة عالية وأسعار مناسبة. نسعى لتقديم تجربة تسوق سهلة وممتعة لعملائنا في جميع أنحاء مصر.",
                'content_en' => "{$legalNoticeEn}\n\nDersey is an Egyptian online fashion store offering the latest trends for men, women, and kids with high quality and fair prices. We aim to provide an easy and enjoyable shopping experience for our customers across Egypt.",
            ],
            [
                'template' => 'contact',
                'slug_ar' => 'اتصل-بنا', 'slug_en' => 'contact-us',
                'title_ar' => 'اتصل بنا', 'title_en' => 'Contact Us',
                'meta_ar' => 'تواصل مع فريق خدمة عملاء ديرسي.',
                'meta_en' => 'Get in touch with the Dersey customer service team.',
                'content_ar' => "{$legalNoticeAr}\n\nيسعدنا تواصلك معنا لأي استفسار أو مشكلة. يمكنك مراسلتنا عبر نموذج التواصل أدناه، وسيقوم فريقنا بالرد خلال 24 ساعة عمل.",
                'content_en' => "{$legalNoticeEn}\n\nWe'd love to hear from you. Reach us via the contact form below, and our team will respond within 24 business hours.",
            ],
            [
                'template' => 'shipping-policy',
                'slug_ar' => 'سياسة-الشحن', 'slug_en' => 'shipping-policy',
                'title_ar' => 'سياسة الشحن', 'title_en' => 'Shipping Policy',
                'meta_ar' => 'تفاصيل مواعيد وتكاليف الشحن لطلبات ديرسي.',
                'meta_en' => 'Details on Dersey\'s shipping times and costs.',
                'content_ar' => "{$legalNoticeAr}\n\nنقوم بالشحن لجميع محافظات مصر خلال 2-5 أيام عمل حسب المنطقة. تكلفة الشحن تظهر عند إتمام الطلب حسب المحافظة والوزن.",
                'content_en' => "{$legalNoticeEn}\n\nWe ship to all governorates in Egypt within 2-5 business days depending on the region. Shipping cost is shown at checkout based on governorate and weight.",
            ],
            [
                'template' => 'return-policy',
                'slug_ar' => 'سياسة-الاسترجاع', 'slug_en' => 'return-policy',
                'title_ar' => 'سياسة الاسترجاع', 'title_en' => 'Return Policy',
                'meta_ar' => 'الشروط والأحكام الخاصة باسترجاع المنتجات.',
                'meta_en' => 'Terms and conditions for returning products.',
                'content_ar' => "{$legalNoticeAr}\n\nيمكن استرجاع المنتج خلال 14 يومًا من تاريخ الاستلام بشرط أن يكون بحالته الأصلية مع العلامات والفاتورة. لا يشمل الاسترجاع المنتجات المخفضة أو الملابس الداخلية.",
                'content_en' => "{$legalNoticeEn}\n\nProducts may be returned within 14 days of delivery, provided they are in original condition with tags and invoice. Sale items and undergarments are excluded from returns.",
            ],
            [
                'template' => 'terms',
                'slug_ar' => 'الشروط-والأحكام', 'slug_en' => 'terms-and-conditions',
                'title_ar' => 'الشروط والأحكام', 'title_en' => 'Terms & Conditions',
                'meta_ar' => 'الشروط والأحكام الخاصة باستخدام متجر ديرسي.',
                'meta_en' => 'Terms and conditions for using the Dersey store.',
                'content_ar' => "{$legalNoticeAr}\n\nباستخدامك موقع ديرسي فإنك توافق على الشروط والأحكام الموضحة هنا، والتي تحكم عمليات الشراء والدفع والتوصيل والحسابات على المنصة.",
                'content_en' => "{$legalNoticeEn}\n\nBy using the Dersey website, you agree to the terms and conditions outlined here, governing purchases, payments, delivery, and account usage on the platform.",
            ],
            [
                'template' => 'privacy',
                'slug_ar' => 'سياسة-الخصوصية', 'slug_en' => 'privacy-policy',
                'title_ar' => 'سياسة الخصوصية', 'title_en' => 'Privacy Policy',
                'meta_ar' => 'كيف يقوم ديرسي بجمع واستخدام بياناتك.',
                'meta_en' => 'How Dersey collects and uses your data.',
                'content_ar' => "{$legalNoticeAr}\n\nنحن نحترم خصوصيتك ونلتزم بحماية بياناتك الشخصية. نستخدم بياناتك فقط لإتمام طلباتك وتحسين تجربتك ولا نشاركها مع أي طرف ثالث دون موافقتك.",
                'content_en' => "{$legalNoticeEn}\n\nWe respect your privacy and are committed to protecting your personal data. Your data is used only to fulfill your orders and improve your experience, and is never shared with third parties without consent.",
            ],
        ];
    }
}
