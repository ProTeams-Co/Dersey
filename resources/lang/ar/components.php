<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Component Language Lines
    |--------------------------------------------------------------------------
    |
    | Strings intrinsic to a components/* Blade component itself (aria-labels,
    | instructional copy it renders regardless of caller) — not the caller-
    | supplied slot content (button text, badge text, etc.), which comes from
    | whichever page/lang file is using the component.
    |
    */

    'form' => [
        'char_count' => ':current / :max',
        'quantity_decrease' => 'تقليل الكمية',
        'quantity_increase' => 'زيادة الكمية',
        'file_drop_instruction' => 'اسحب وأفلت ملف هنا',
        'file_browse' => 'أو اضغط للاختيار',
    ],

    'pagination' => [
        'nav_label' => 'الصفحات',
        'previous' => 'السابق',
        'next' => 'التالي',
        'go_to_page' => 'اذهب للصفحة :page',
        'current_page' => 'الصفحة الحالية',
    ],

    'breadcrumb' => [
        'nav_label' => 'مسار التصفح',
    ],

    'rating' => [
        'label' => ':rating من 5',
        'set_rating' => 'قيّم بـ :rating من 5',
    ],

    'tabs' => [
        'nav_label' => 'تبويبات',
    ],

];
