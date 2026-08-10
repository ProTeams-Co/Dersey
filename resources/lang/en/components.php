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
        'quantity_decrease' => 'Decrease quantity',
        'quantity_increase' => 'Increase quantity',
        'file_drop_instruction' => 'Drag and drop a file here',
        'file_browse' => 'or click to browse',
    ],

    'pagination' => [
        'nav_label' => 'Pagination',
        'previous' => 'Previous',
        'next' => 'Next',
        'go_to_page' => 'Go to page :page',
        'current_page' => 'Current page',
    ],

    'breadcrumb' => [
        'nav_label' => 'Breadcrumb',
    ],

    'rating' => [
        'label' => ':rating out of 5',
        'set_rating' => 'Rate :rating out of 5',
    ],

    'tabs' => [
        'nav_label' => 'Tabs',
    ],

];
