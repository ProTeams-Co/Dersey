<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\FaqTranslation;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $categories = [];

        foreach ($this->categories() as $sort => [$nameAr, $nameEn]) {
            $categories[$nameAr] = FaqCategory::create([
                'name' => ['ar' => $nameAr, 'en' => $nameEn],
                'sort' => $sort,
            ]);
        }

        foreach ($this->faqs() as $sort => $data) {
            $faq = Faq::create([
                'faq_category_id' => $categories[$data['category']]->id,
                'sort' => $sort,
                'is_active' => true,
            ]);

            FaqTranslation::create([
                'faq_id' => $faq->id,
                'locale' => 'ar',
                'question' => $data['question_ar'],
                'answer' => $data['answer_ar'],
            ]);

            FaqTranslation::create([
                'faq_id' => $faq->id,
                'locale' => 'en',
                'question' => $data['question_en'],
                'answer' => $data['answer_en'],
            ]);
        }
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function categories(): array
    {
        return [
            ['الطلبات والشحن', 'Orders & Shipping'],
            ['الدفع', 'Payment'],
            ['الاسترجاع', 'Returns'],
            ['الحساب', 'Account'],
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function faqs(): array
    {
        return [
            ['category' => 'الطلبات والشحن', 'question_ar' => 'كام مدة توصيل الطلب؟', 'question_en' => 'How long does delivery take?', 'answer_ar' => 'مدة التوصيل من 2 إلى 5 أيام عمل حسب المحافظة.', 'answer_en' => 'Delivery takes 2 to 5 business days depending on the governorate.'],
            ['category' => 'الطلبات والشحن', 'question_ar' => 'إزاي أتابع طلبي؟', 'question_en' => 'How do I track my order?', 'answer_ar' => 'يمكنك متابعة طلبك من صفحة "طلباتي" بعد تسجيل الدخول.', 'answer_en' => 'You can track your order from the "My Orders" page after logging in.'],
            ['category' => 'الطلبات والشحن', 'question_ar' => 'هل الشحن متاح لكل المحافظات؟', 'question_en' => 'Is shipping available to all governorates?', 'answer_ar' => 'نعم، نوفر الشحن لجميع محافظات مصر.', 'answer_en' => 'Yes, we ship to all governorates in Egypt.'],
            ['category' => 'الدفع', 'question_ar' => 'إيه وسائل الدفع المتاحة؟', 'question_en' => 'What payment methods are available?', 'answer_ar' => 'بنقبل الدفع بالبطاقات الائتمانية والدفع عند الاستلام عبر بوابة Paymob.', 'answer_en' => 'We accept credit cards and cash on delivery via Paymob.'],
            ['category' => 'الدفع', 'question_ar' => 'هل بياناتي البنكية آمنة؟', 'question_en' => 'Is my payment information secure?', 'answer_ar' => 'نعم، كل عمليات الدفع بتتم عبر بوابة Paymob المشفرة والآمنة.', 'answer_en' => 'Yes, all payments are processed securely through the encrypted Paymob gateway.'],
            ['category' => 'الدفع', 'question_ar' => 'هل ممكن أدفع عند الاستلام؟', 'question_en' => 'Can I pay on delivery?', 'answer_ar' => 'نعم، الدفع عند الاستلام متاح في معظم المحافظات.', 'answer_en' => 'Yes, cash on delivery is available in most governorates.'],
            ['category' => 'الاسترجاع', 'question_ar' => 'هل ممكن أرجع المنتج؟', 'question_en' => 'Can I return a product?', 'answer_ar' => 'ده متجر بيع نهائي، مفيش إرجاع أو تبديل بعد إتمام عملية الشراء.', 'answer_en' => 'This is a final-sale store; no returns or exchanges after purchase.'],
            ['category' => 'الاسترجاع', 'question_ar' => 'المنتج وصل معيب، أعمل إيه؟', 'question_en' => 'The product arrived defective, what should I do?', 'answer_ar' => 'تواصل معانا فورًا عبر صفحة اتصل بنا وهنساعدك في حل المشكلة.', 'answer_en' => 'Contact us immediately via the Contact Us page and we\'ll help resolve it.'],
            ['category' => 'الحساب', 'question_ar' => 'إزاي أعمل حساب جديد؟', 'question_en' => 'How do I create a new account?', 'answer_ar' => 'اضغط على "تسجيل" في أعلى الصفحة واملأ بياناتك الأساسية.', 'answer_en' => 'Click "Sign Up" at the top of the page and fill in your basic details.'],
            ['category' => 'الحساب', 'question_ar' => 'نسيت كلمة السر، أعمل إيه؟', 'question_en' => 'I forgot my password, what should I do?', 'answer_ar' => 'استخدم رابط "نسيت كلمة السر" في صفحة تسجيل الدخول.', 'answer_en' => 'Use the "Forgot Password" link on the login page.'],
            ['category' => 'الحساب', 'question_ar' => 'إزاي أغيّر بياناتي الشخصية؟', 'question_en' => 'How do I update my personal information?', 'answer_ar' => 'من صفحة "حسابي" يمكنك تعديل بياناتك في أي وقت.', 'answer_en' => 'You can edit your details anytime from the "My Account" page.'],
            ['category' => 'الطلبات والشحن', 'question_ar' => 'هل ممكن أغيّر عنوان الشحن بعد الطلب؟', 'question_en' => 'Can I change the shipping address after ordering?', 'answer_ar' => 'يمكن التعديل فقط قبل شحن الطلب، تواصل معانا بسرعة.', 'answer_en' => 'Changes are only possible before the order ships; contact us quickly.'],
        ];
    }
}
