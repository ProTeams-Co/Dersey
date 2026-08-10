<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Design System Page
    |--------------------------------------------------------------------------
    |
    | Permanent, local-only component library preview (resources/views/
    | design-system.blade.php) — every label on that page, including demo
    | copy, comes from here rather than being hardcoded in the view.
    |
    */

    'title' => 'مكتبة المكوّنات',
    'subtitle' => 'كل مكوّنات Blade القابلة لإعادة الاستخدام، بكل حالاتها — بيئة local فقط.',
    'dir_rtl' => 'RTL',
    'dir_ltr' => 'LTR',

    'sections' => [
        'colors' => 'الألوان',
        'typography' => 'مقياس الخطوط',
        'form' => 'مكوّنات النماذج',
        'buttons' => 'الأزرار',
        'badges' => 'الشارات',
        'chips' => 'الرقاقات',
        'alerts' => 'التنبيهات',
        'feedback' => 'مؤشرات التحميل',
        'tooltip' => 'تلميحات',
        'cards' => 'البطاقات',
        'product_card' => 'بطاقة المنتج',
        'breadcrumb' => 'مسار التصفح',
        'pagination' => 'ترقيم الصفحات',
        'empty_state' => 'حالة فارغة',
        'rating' => 'التقييم',
        'tabs' => 'التبويبات',
        'accordion' => 'الأكورديون',
    ],

    'states' => [
        'default' => 'افتراضي',
        'focus' => 'تركيز (Tab)',
        'error' => 'خطأ',
        'disabled' => 'معطّل',
        'checked' => 'محدَّد',
        'indeterminate' => 'غير محدَّد جزئيًا',
    ],

    'demo' => [
        'input_label' => 'الاسم الكامل',
        'input_placeholder' => 'اكتب اسمك...',
        'input_hint' => 'زي ما هو مكتوب في البطاقة.',
        'input_error' => 'الاسم مطلوب.',

        'textarea_label' => 'ملاحظات الطلب',
        'textarea_placeholder' => 'أي تعليمات خاصة بالتوصيل...',

        'select_label' => 'المحافظة',
        'select_placeholder' => 'اختر محافظة',
        'select_option_cairo' => 'القاهرة',
        'select_option_giza' => 'الجيزة',
        'select_option_alex' => 'الإسكندرية',

        'checkbox_label' => 'أوافق على الشروط والأحكام',
        'checkbox_label_2' => 'اشتراك في النشرة البريدية',
        'radio_label_card' => 'الدفع بالكارت',
        'radio_label_wallet' => 'الدفع بالمحفظة',
        'toggle_label' => 'تفعيل الإشعارات',

        'file_label' => 'صورة المنتج',
        'file_hint' => 'PNG أو JPG بحد أقصى 5 ميجابايت.',

        'button_primary' => 'إتمام الطلب',
        'button_secondary' => 'حفظ كمسودة',
        'button_outline' => 'إلغاء',
        'button_ghost' => 'تخطي',
        'button_danger' => 'حذف الحساب',
        'button_loading' => 'جارٍ الإرسال',

        'badge_new' => 'جديد',
        'badge_sale' => 'خصم',
        'badge_out_of_stock' => 'نفدت الكمية',
        'badge_limited' => 'كمية محدودة',

        'chip_size' => 'مقاس L',
        'chip_color' => 'أخضر',
        'chip_price' => 'أقل من 500 ج.م',

        'alert_info' => 'التوصيل بياخد من 2 لـ 4 أيام عمل داخل القاهرة والجيزة.',
        'alert_success' => 'تم تأكيد طلبك بنجاح.',
        'alert_warning' => 'الكمية المتاحة من المقاس ده محدودة.',
        'alert_danger' => 'حصل خطأ أثناء معالجة الدفع.',

        'tooltip_trigger' => 'ليه محتاج المقاس ده؟',
        'tooltip_text' => 'دليل المقاسات الكامل في صفحة المنتج.',

        'card_title' => 'عنوان البطاقة',
        'card_body' => 'محتوى تجريبي يوضح شكل البطاقة الأساسية بأقسامها التلاتة.',
        'card_footer_action' => 'عرض التفاصيل',

        'product_name' => 'فستان صيفي كتان',

        'breadcrumb_home' => 'الرئيسية',
        'breadcrumb_category' => 'فساتين',
        'breadcrumb_product' => 'فستان صيفي كتان',

        'empty_title' => 'مفيش نتائج',
        'empty_description' => 'جرّب تغيّر الفلاتر أو ابحث بكلمة تانية.',
        'empty_cta' => 'مسح الفلاتر',

        'tab_overview' => 'نظرة عامة',
        'tab_details' => 'التفاصيل',
        'tab_reviews' => 'التقييمات',
        'tab_overview_content' => 'محتوى تبويب النظرة العامة — تنقّل بينه وبين باقي التبويبات بالأسهم بعد التركيز على أي زرار.',
        'tab_details_content' => 'محتوى تبويب التفاصيل.',
        'tab_reviews_content' => 'محتوى تبويب التقييمات.',

        'accordion_shipping_title' => 'الشحن والتوصيل',
        'accordion_shipping_content' => 'التوصيل لكل محافظات مصر خلال 2-5 أيام عمل.',
        'accordion_returns_title' => 'سياسة الاستبدال',
        'accordion_returns_content' => 'المنتجات دي بيع نهائي — مفيش استرجاع أو استبدال.',
        'accordion_payment_title' => 'طرق الدفع',
        'accordion_payment_content' => 'الدفع بالكارت أو المحفظة الإلكترونية عبر Paymob.',
    ],

];
