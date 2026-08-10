<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Common Language Lines
    |--------------------------------------------------------------------------
    |
    | General-purpose strings shared across the storefront: buttons, generic
    | navigation labels, and other text with no single page it belongs to.
    | Page-specific copy belongs in that page's own view/controller, not here.
    |
    */

    'add_to_cart' => 'أضف إلى السلة',
    'buy_now' => 'اشترِ الآن',
    'save' => 'حفظ',
    'cancel' => 'إلغاء',
    'confirm' => 'تأكيد',
    'close' => 'إغلاق',
    'search' => 'بحث',
    'view_all' => 'عرض الكل',
    'loading' => 'جارٍ التحميل…',
    'back' => 'رجوع',
    'continue' => 'متابعة',
    'remove' => 'إزالة',
    'currency_symbol' => 'ج.م',
    'language_switcher' => 'مبدّل اللغة',

    /**
     * Arabic has 6 grammatical plural forms — collapsing this to Laravel's
     * simple two-form "singular|plural" pipe syntax would mis-inflect every
     * count except exactly 1. {0}/{1}/{2} are exact matches (zero/one/two —
     * dual has its own noun form in Arabic, not "2 X"); [3,10] is "few"
     * (plural noun form: منتجات), [11,99] is "many" (singular-after-number
     * form: منتجًا), and [100,*] reverts to the singular noun form again —
     * this is standard Arabic numeral-noun agreement, not a rounding choice.
     */
    'product_count' => '{0} لا توجد منتجات|{1} منتج واحد|{2} منتجان|[3,10] :count منتجات|[11,99] :count منتجًا|[100,*] :count منتج',

];
