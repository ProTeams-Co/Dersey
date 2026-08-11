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
        'active' => 'Active',
        'inactive' => 'Inactive',
        'banned' => 'Banned',
    ],

    'admin_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'gender' => [
        'men' => 'Men',
        'women' => 'Women',
        'unisex' => 'Unisex',
        'kids' => 'Kids',
    ],

    'product_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'order_status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
    ],

    'payment_status' => [
        'unpaid' => 'Unpaid',
        'paid' => 'Paid',
        'partially_refunded' => 'Partially Refunded',
        'refunded' => 'Refunded',
        'failed' => 'Failed',
    ],

    'payment_method' => [
        'card' => 'Card',
        'wallet' => 'E-Wallet',
        'kiosk' => 'Fawry',
        'valu' => 'valU',
        'cod' => 'Cash on Delivery',
    ],

    'inventory_movement_type' => [
        'in' => 'In',
        'out' => 'Out',
        'reserve' => 'Reserved',
        'release' => 'Released',
        'adjust' => 'Adjustment',
    ],

    'discount_type' => [
        'fixed' => 'Fixed Amount',
        'percent' => 'Percentage',
        'free_shipping' => 'Free Shipping',
    ],

    'attribute_type' => [
        'select' => 'Select List',
        'color' => 'Color',
        'text' => 'Text',
    ],

    'shipping_method_type' => [
        'flat' => 'Flat Rate',
        'weight_based' => 'Weight-Based',
        'free_over' => 'Free Over a Threshold',
    ],

    'refund_status' => [
        'pending' => 'Pending',
        'processed' => 'Processed',
        'failed' => 'Failed',
    ],

    'return_request_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
    ],

    'post_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'scheduled' => 'Scheduled',
    ],

    'review_status' => [
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'contact_message_status' => [
        'new' => 'New',
        'read' => 'Read',
        'replied' => 'Replied',
    ],

    'banner_position' => [
        'hero' => 'Hero',
        'mid' => 'Mid-Page',
        'footer' => 'Footer',
        'category' => 'Category Page',
    ],

    'redirect_status_code' => [
        '301' => 'Permanent (301)',
        '302' => 'Temporary (302)',
    ],

];
