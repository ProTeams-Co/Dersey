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

    'title' => 'Component Library',
    'subtitle' => 'Every reusable Blade component, in every state — local environment only.',
    'dir_rtl' => 'RTL',
    'dir_ltr' => 'LTR',

    'sections' => [
        'colors' => 'Colors',
        'typography' => 'Type Scale',
        'form' => 'Form Components',
        'buttons' => 'Buttons',
        'badges' => 'Badges',
        'chips' => 'Chips',
        'alerts' => 'Alerts',
        'feedback' => 'Loading Indicators',
        'tooltip' => 'Tooltip',
        'cards' => 'Cards',
        'product_card' => 'Product Card',
        'breadcrumb' => 'Breadcrumb',
        'pagination' => 'Pagination',
        'empty_state' => 'Empty State',
        'rating' => 'Rating',
        'tabs' => 'Tabs',
        'accordion' => 'Accordion',
    ],

    'states' => [
        'default' => 'Default',
        'focus' => 'Focus (Tab)',
        'error' => 'Error',
        'disabled' => 'Disabled',
        'checked' => 'Checked',
        'indeterminate' => 'Indeterminate',
    ],

    'demo' => [
        'input_label' => 'Full Name',
        'input_placeholder' => 'Enter your name...',
        'input_hint' => 'As printed on your card.',
        'input_error' => 'Name is required.',

        'textarea_label' => 'Order Notes',
        'textarea_placeholder' => 'Any special delivery instructions...',

        'select_label' => 'Governorate',
        'select_placeholder' => 'Select a governorate',
        'select_option_cairo' => 'Cairo',
        'select_option_giza' => 'Giza',
        'select_option_alex' => 'Alexandria',

        'checkbox_label' => 'I agree to the terms and conditions',
        'checkbox_label_2' => 'Subscribe to the newsletter',
        'radio_label_card' => 'Pay by card',
        'radio_label_wallet' => 'Pay by wallet',
        'toggle_label' => 'Enable notifications',

        'file_label' => 'Product image',
        'file_hint' => 'PNG or JPG, up to 5MB.',

        'button_primary' => 'Checkout',
        'button_secondary' => 'Save draft',
        'button_outline' => 'Cancel',
        'button_ghost' => 'Skip',
        'button_danger' => 'Delete account',
        'button_loading' => 'Submitting',

        'badge_new' => 'New',
        'badge_sale' => 'Sale',
        'badge_out_of_stock' => 'Out of stock',
        'badge_limited' => 'Limited stock',

        'chip_size' => 'Size L',
        'chip_color' => 'Green',
        'chip_price' => 'Under EGP 500',

        'alert_info' => 'Delivery takes 2-4 business days within Cairo and Giza.',
        'alert_success' => 'Your order has been confirmed.',
        'alert_warning' => 'Stock for this size is limited.',
        'alert_danger' => 'An error occurred while processing your payment.',

        'tooltip_trigger' => 'Why do we need this size?',
        'tooltip_text' => 'The full size guide is on the product page.',

        'card_title' => 'Card Title',
        'card_body' => 'Sample content illustrating the basic card in its three sections.',
        'card_footer_action' => 'View details',

        'product_name' => 'Linen Summer Dress',

        'breadcrumb_home' => 'Home',
        'breadcrumb_category' => 'Dresses',
        'breadcrumb_product' => 'Linen Summer Dress',

        'empty_title' => 'No results',
        'empty_description' => 'Try changing the filters or searching a different term.',
        'empty_cta' => 'Clear filters',

        'tab_overview' => 'Overview',
        'tab_details' => 'Details',
        'tab_reviews' => 'Reviews',
        'tab_overview_content' => 'Overview tab content — move between tabs with the arrow keys after focusing any tab button.',
        'tab_details_content' => 'Details tab content.',
        'tab_reviews_content' => 'Reviews tab content.',

        'accordion_shipping_title' => 'Shipping & Delivery',
        'accordion_shipping_content' => 'Delivery to all Egyptian governorates within 2-5 business days.',
        'accordion_returns_title' => 'Exchange Policy',
        'accordion_returns_content' => 'These products are final sale — no returns or exchanges.',
        'accordion_payment_title' => 'Payment Methods',
        'accordion_payment_content' => 'Pay by card or e-wallet via Paymob.',
    ],

];
