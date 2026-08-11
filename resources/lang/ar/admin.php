<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel
    |--------------------------------------------------------------------------
    |
    | Every string shown in the admin panel comes from here (or another
    | admin.* section added alongside it) - the panel is Arabic-only, but
    | still uses lang files rather than hardcoded text (CLAUDE.md §11), for
    | easy review/editing.
    |
    */

    'auth' => [
        'title' => 'تسجيل الدخول',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'remember_me' => 'تذكرني',
        'login_button' => 'دخول',
        'forgot_password' => 'نسيت كلمة المرور؟',
        'failed' => 'بيانات الدخول غير صحيحة.',
        'suspended' => 'هذا الحساب موقوف. تواصل مع مدير النظام.',
        'throttle' => 'محاولات دخول كثيرة جدًا. حاول مرة أخرى بعد :seconds ثانية.',
        'logout' => 'تسجيل الخروج',

        'forgot_password_title' => 'استعادة كلمة المرور',
        'forgot_password_intro' => 'أدخل بريدك الإلكتروني وسنرسل لك رابط إعادة تعيين كلمة المرور.',
        'send_reset_link' => 'إرسال رابط الاستعادة',
        'reset_link_sent' => 'تم إرسال رابط استعادة كلمة المرور إلى بريدك الإلكتروني إذا كان مسجّلاً لدينا.',
        'back_to_login' => 'الرجوع لتسجيل الدخول',

        'reset_password_title' => 'إعادة تعيين كلمة المرور',
        'new_password' => 'كلمة المرور الجديدة',
        'confirm_password' => 'تأكيد كلمة المرور',
        'reset_button' => 'إعادة التعيين',
        'password_reset' => 'تم تغيير كلمة المرور بنجاح، سجّل الدخول بكلمة المرور الجديدة.',
        'invalid_token' => 'رابط استعادة كلمة المرور غير صالح أو منتهي الصلاحية.',
        'invalid_user' => 'لا يوجد حساب مسجّل بهذا البريد الإلكتروني.',
        'reset_throttled' => 'الرجاء الانتظار قبل طلب رابط استعادة آخر.',
        'reset_failed' => 'تعذّرت إعادة تعيين كلمة المرور. حاول مرة أخرى.',

        'reset_mail_subject' => 'استعادة كلمة المرور - Dersey',
        'reset_mail_greeting' => 'مرحبًا :name،',
        'reset_mail_line' => 'وصلنا طلب لإعادة تعيين كلمة المرور الخاصة بحسابك في لوحة تحكم Dersey.',
        'reset_mail_action' => 'إعادة تعيين كلمة المرور',
        'reset_mail_expire' => 'ينتهي صلاحية هذا الرابط خلال :minutes دقيقة. إذا لم تطلب استعادة كلمة المرور، تجاهل هذه الرسالة.',
    ],

    'dashboard' => [
        'title' => 'لوحة التحكم',
        'stat_orders_today' => 'طلبات النهاردة',
        'stat_revenue_today' => 'إيرادات النهاردة',
        'stat_low_stock' => 'منتجات وشك تخلص',
        'stat_pending_reviews' => 'مراجعات في الانتظار',
        'recent_orders' => 'آخر الطلبات',
        'low_stock_alerts' => 'تنبيهات المخزون المنخفض',
        'sales_chart_title' => 'المبيعات آخر 7 أيام',
        'no_recent_orders' => 'مفيش طلبات لسه.',
        'no_low_stock' => 'مفيش منتجات وشك تخلص.',
        'view_all' => 'عرض الكل',
        'demo_data_notice' => 'بيانات الرسم البياني تجريبية مؤقتًا.',
    ],

    'admins' => [
        'title' => 'المستخدمون الإداريون',
        'column_name' => 'الاسم',
        'column_email' => 'البريد الإلكتروني',
        'column_status' => 'الحالة',
        'column_last_login' => 'آخر دخول',
        'column_created' => 'تاريخ الإنشاء',
        'never_logged_in' => 'لسه ما دخلش',
    ],

    'menu' => [
        'dashboard' => 'الرئيسية',
        'catalog' => 'الكتالوج',
        'products' => 'المنتجات',
        'categories' => 'التصنيفات',
        'inventory' => 'المخزون',
        'orders' => 'الطلبات',
        'customers' => 'العملاء',
        'addresses' => 'العناوين',
        'discounts' => 'الخصومات',
        'settings' => 'الإعدادات',
        'administration' => 'إدارة النظام',
        'admins' => 'المستخدمون الإداريون',
        'roles' => 'الأدوار والصلاحيات',
        'coming_soon' => 'قريبًا',
    ],

    'form' => [
        'language_tabs' => 'لغات المحتوى',
        'language_incomplete' => 'فيه حقول مطلوبة ناقصة في اللغة دي',
        'add_row' => 'إضافة صف',
    ],

    'crud' => [
        'created' => 'تم الإنشاء بنجاح.',
        'updated' => 'تم التحديث بنجاح.',
        'deleted' => 'تم الحذف بنجاح.',
        'bulk_deleted' => 'تم حذف :count عنصر بنجاح.',
    ],

    'table' => [
        'search_placeholder' => 'بحث...',
        'no_results_title' => 'مفيش نتائج',
        'no_results_description' => 'جرّب تغيّر كلمة البحث أو الفلاتر.',
        'showing' => 'عرض :from-:to من :total',
        'filters' => 'فلاتر',
        'apply_filters' => 'تطبيق',
        'clear_filters' => 'مسح الفلاتر',
        'select_all' => 'تحديد الكل',
        'selected_count' => 'محدد :count',
        'bulk_actions' => 'إجراءات جماعية',
        'export' => 'تصدير CSV',
        'export_queued' => 'التصدير كبير، هيتبعتلك لينك التحميل قريب.',
        'confirm_bulk_action' => 'متأكد إنك عايز تنفّذ الإجراء ده على العناصر المحددة؟',
        'actions' => 'إجراءات',
    ],

    'layout' => [
        'collapse_sidebar' => 'طي القائمة الجانبية',
        'expand_sidebar' => 'توسيع القائمة الجانبية',
        'open_menu' => 'فتح القائمة',
        'close_menu' => 'إغلاق القائمة',
        'search_placeholder' => 'بحث سريع...',
        'notifications' => 'الإشعارات',
        'no_notifications' => 'مفيش إشعارات جديدة.',
        'account' => 'الحساب',
        'profile' => 'الملف الشخصي',
        'skip_to_content' => 'تخطي إلى المحتوى',
        'breadcrumb_home' => 'الرئيسية',
    ],

];
