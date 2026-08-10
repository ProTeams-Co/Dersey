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
        'free_shipping' => 'Free shipping on orders over EGP 1,500',
        'contact' => 'Contact us',
    ],

    'nav' => [
        'menu' => 'Menu',
        'open_menu' => 'Open menu',
        'close_menu' => 'Close menu',
        'account' => 'My Account',
        'wishlist' => 'Wishlist',
        'cart' => 'Cart',
        'cart_count' => 'Items in cart',
        'search' => 'Search',
    ],

    'categories' => [
        'dresses' => [
            'name' => 'Dresses',
            'subcategories' => ['Evening Dresses', 'Casual Dresses', 'Occasion Dresses', 'Mini Dresses'],
        ],
        'blouses' => [
            'name' => 'Blouses',
            'subcategories' => ['Casual Blouses', 'Formal Blouses', 'Shirts', 'Tops'],
        ],
        'pants' => [
            'name' => 'Pants',
            'subcategories' => ['Jeans', 'Trousers', 'Leggings', 'Shorts'],
        ],
        'jackets' => [
            'name' => 'Jackets',
            'subcategories' => ['Denim Jackets', 'Winter Jackets', 'Blazers', 'Cardigans'],
        ],
        'accessories' => [
            'name' => 'Accessories',
            'subcategories' => ['Bags', 'Shoes', 'Hair Accessories', 'Jewelry'],
        ],
    ],

    'mega_menu' => [
        'shop_by_type' => 'Shop by Type',
        'quick_links_heading' => 'Quick Links',
        'new_in' => 'New In',
        'best_sellers' => 'Best Sellers',
        'sale' => 'Sale',
        'promo_heading' => 'The New Season Edit',
        'promo_cta' => 'Discover the Collection',
    ],

    'mobile_nav' => [
        'heading' => 'Menu',
    ],

    'cart' => [
        'heading' => 'Shopping Cart',
        'empty_heading' => 'Your cart is empty',
        'empty_description' => "You haven't added anything to your cart yet.",
        'empty_cta' => 'Continue Shopping',
        'item_size' => 'Size',
        'item_color' => 'Color',
        'quantity' => 'Quantity',
        'remove' => 'Remove',
        'subtotal' => 'Subtotal',
        'checkout' => 'Checkout',
        'continue_shopping' => 'Continue Shopping',
    ],

    'search' => [
        'heading' => 'Search',
        'placeholder' => 'Search for a product...',
        'popular_heading' => 'Popular Searches',
        'popular_terms' => ['Evening Dresses', 'Denim Jacket', 'Shoes', 'Accessories'],
        'results_heading' => 'Results',
        'results_placeholder' => 'Start typing to see results.',
    ],

    'footer' => [
        'logo_alt' => 'Dersey',
        'ptc_logo_alt' => 'Pro Teams Co.',
        'copyright' => '© :year Dersey. All rights reserved.',

        'about_heading' => 'About Dersey',
        'about_text' => 'Dersey is an online fashion store for the Egyptian market — carefully curated collections at fair prices.',

        'customer_service_heading' => 'Customer Service',
        'customer_service_links' => [
            'faq' => 'FAQ',
            'shipping' => 'Shipping & Delivery',
            'returns' => 'Exchange Policy',
            'size_guide' => 'Size Guide',
            'contact_us' => 'Contact Us',
        ],

        'categories_heading' => 'Categories',

        'contact_heading' => 'Contact',
        'contact_email' => 'support@dersey.com',
        'contact_phone' => '19000',
        'contact_address' => 'Cairo, Egypt',

        'newsletter_heading' => 'Newsletter',
        'newsletter_description' => 'Subscribe to get our latest offers first.',
        'newsletter_placeholder' => 'Your email address',
        'newsletter_cta' => 'Subscribe',

        'social_heading' => 'Follow Us',
        'payment_heading' => 'Payment Methods',
    ],

];
