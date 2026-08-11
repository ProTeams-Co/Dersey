<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enum Language Lines
    |--------------------------------------------------------------------------
    |
    | One group per App\Enums\* case, keyed by the enum's snake_case class
    | name then its string value — consumed exclusively via that enum's own
    | label() method, never called directly with a raw key from outside it.
    |
    */

    'user_status' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'banned' => 'محظور',
    ],

    'admin_status' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
    ],

    'gender' => [
        'men' => 'رجالي',
        'women' => 'حريمي',
        'unisex' => 'للجنسين',
        'kids' => 'أطفال',
    ],

    'product_status' => [
        'draft' => 'مسودة',
        'published' => 'منشور',
        'archived' => 'مؤرشف',
    ],

    'order_status' => [
        'pending' => 'قيد الانتظار',
        'confirmed' => 'مؤكَّد',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'out_for_delivery' => 'خارج للتوصيل',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي',
        'returned' => 'مرتجَع',
    ],

    'payment_status' => [
        'unpaid' => 'غير مدفوع',
        'paid' => 'مدفوع',
        'partially_refunded' => 'مسترجَع جزئيًا',
        'refunded' => 'مسترجَع',
        'failed' => 'فشل',
    ],

    'payment_method' => [
        'card' => 'كارت',
        'wallet' => 'محفظة إلكترونية',
        'kiosk' => 'فوري',
        'valu' => 'فاليو',
        'cod' => 'الدفع عند الاستلام',
    ],

    'inventory_movement_type' => [
        'in' => 'وارد',
        'out' => 'صادر',
        'reserve' => 'محجوز',
        'release' => 'إفراج',
        'adjust' => 'تسوية',
    ],

    'discount_type' => [
        'fixed' => 'مبلغ ثابت',
        'percent' => 'نسبة مئوية',
        'free_shipping' => 'شحن مجاني',
    ],

    'attribute_type' => [
        'select' => 'قائمة اختيار',
        'color' => 'لون',
        'text' => 'نص',
    ],

    'shipping_method_type' => [
        'flat' => 'سعر ثابت',
        'weight_based' => 'حسب الوزن',
        'free_over' => 'مجاني فوق حد معيّن',
    ],

    'refund_status' => [
        'pending' => 'قيد الانتظار',
        'processed' => 'تم الاسترجاع',
        'failed' => 'فشل',
    ],

    'return_request_status' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
        'completed' => 'مكتمل',
    ],

    'post_status' => [
        'draft' => 'مسودة',
        'published' => 'منشور',
        'scheduled' => 'مجدول',
    ],

    'review_status' => [
        'pending' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
    ],

    'contact_message_status' => [
        'new' => 'جديدة',
        'read' => 'مقروءة',
        'replied' => 'تم الرد',
    ],

    'banner_position' => [
        'hero' => 'رئيسي',
        'mid' => 'وسط الصفحة',
        'footer' => 'أسفل الصفحة',
        'category' => 'صفحة تصنيف',
    ],

    'redirect_status_code' => [
        '301' => 'دائم (301)',
        '302' => 'مؤقت (302)',
    ],

];
