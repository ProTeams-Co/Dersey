<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Layout Language Lines
    |--------------------------------------------------------------------------
    |
    | Header, navigation, drawers, search overlay, and footer — every string
    | rendered by resources/views/components/layout/* and their JS modules.
    | Category/menu content here is placeholder structure for Batch 1.5 only,
    | not real catalog data.
    |
    */

    'top_bar' => [
        'free_shipping' => 'شحن مجاني للطلبات فوق 1500 ج.م',
        'contact' => 'تواصل معنا',
    ],

    'nav' => [
        'menu' => 'القائمة',
        'open_menu' => 'فتح القائمة',
        'close_menu' => 'إغلاق القائمة',
        'account' => 'حسابي',
        'wishlist' => 'المفضلة',
        'cart' => 'السلة',
        'cart_count' => 'عدد المنتجات في السلة',
        'search' => 'بحث',
    ],

    'categories' => [
        'dresses' => [
            'name' => 'فساتين',
            'subcategories' => ['فساتين سهرة', 'فساتين يومي', 'فساتين مناسبات', 'فساتين قصيرة'],
        ],
        'blouses' => [
            'name' => 'بلوزات',
            'subcategories' => ['بلوزات كاجوال', 'بلوزات رسمي', 'قمصان', 'توب'],
        ],
        'pants' => [
            'name' => 'بناطيل',
            'subcategories' => ['جينز', 'بنطلون قماش', 'ليجن', 'شورت'],
        ],
        'jackets' => [
            'name' => 'جاكيتات',
            'subcategories' => ['جاكيت جينز', 'جاكيت شتوي', 'بليزر', 'كارديجان'],
        ],
        'accessories' => [
            'name' => 'إكسسوارات',
            'subcategories' => ['شنط', 'أحذية', 'إكسسوارات شعر', 'مجوهرات'],
        ],
    ],

    'mega_menu' => [
        'shop_by_type' => 'تسوق حسب النوع',
        'quick_links_heading' => 'روابط سريعة',
        'new_in' => 'وصل حديثًا',
        'best_sellers' => 'الأكثر مبيعًا',
        'sale' => 'التخفيضات',
        'promo_heading' => 'مجموعة الموسم الجديدة',
        'promo_cta' => 'اكتشف المجموعة',
    ],

    'mobile_nav' => [
        'heading' => 'القائمة',
    ],

    'cart' => [
        'heading' => 'سلة التسوق',
        'empty_heading' => 'السلة فاضية',
        'empty_description' => 'لسه مضفتش أي منتج للسلة.',
        'empty_cta' => 'أكمل التسوق',
        'item_size' => 'المقاس',
        'item_color' => 'اللون',
        'quantity' => 'الكمية',
        'remove' => 'حذف',
        'subtotal' => 'الإجمالي الفرعي',
        'checkout' => 'إتمام الطلب',
        'continue_shopping' => 'أكمل التسوق',
    ],

    'search' => [
        'heading' => 'البحث',
        'placeholder' => 'ابحث عن منتج...',
        'popular_heading' => 'عمليات بحث شائعة',
        'popular_terms' => ['فساتين سهرة', 'جاكيت جينز', 'أحذية', 'إكسسوارات'],
        'results_heading' => 'النتائج',
        'results_placeholder' => 'اكتب عشان تشوف النتائج.',
    ],

    'footer' => [
        'logo_alt' => 'Dersey',
        'ptc_logo_alt' => 'Pro Teams Co.',
        'copyright' => '© :year Dersey. كل الحقوق محفوظة.',

        'about_heading' => 'عن المتجر',
        'about_text' => 'Dersey متجر أزياء إلكتروني للسوق المصري — تشكيلات مختارة بعناية بجودة وسعر عادل.',

        'customer_service_heading' => 'خدمة العملاء',
        'customer_service_links' => [
            'faq' => 'الأسئلة الشائعة',
            'shipping' => 'الشحن والتوصيل',
            'returns' => 'سياسة الاستبدال',
            'size_guide' => 'دليل المقاسات',
            'contact_us' => 'تواصل معنا',
        ],

        'categories_heading' => 'التصنيفات',

        'contact_heading' => 'تواصل',
        'contact_email' => 'support@dersey.com',
        'contact_phone' => '19000',
        'contact_address' => 'القاهرة، مصر',

        'newsletter_heading' => 'النشرة البريدية',
        'newsletter_description' => 'اشترك عشان توصلك عروضنا الجديدة أول بأول.',
        'newsletter_placeholder' => 'بريدك الإلكتروني',
        'newsletter_cta' => 'اشتراك',

        'social_heading' => 'تابعنا',
        'payment_heading' => 'طرق الدفع',
    ],

];
