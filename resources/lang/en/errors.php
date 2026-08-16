<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    |
    | Messages surfaced by custom exceptions' render() methods (see
    | app/Exceptions) - user-facing business-rule errors, not validation
    | messages (those stay in validation.php).
    |
    */

    'category_has_children' => 'This category still has sub-categories. Move or delete them first.',
    'category_has_products' => 'This category still has products assigned to it. Remove them from this category first.',
    'insufficient_stock' => 'The requested quantity is not currently available in stock.',
    'coupon_limit_reached' => 'This coupon has reached its maximum number of uses.',
    'coupon_inactive' => 'This coupon is not currently active.',
    'coupon_not_started' => 'This coupon is not active yet.',
    'coupon_expired' => 'This coupon has expired.',
    'coupon_min_order_not_met' => 'Your order total is below the minimum required for this coupon.',
    'coupon_usage_limit_reached' => 'This coupon has reached its maximum number of uses.',
    'coupon_user_limit_reached' => 'You have already used this coupon the maximum number of times allowed.',
    'coupon_first_order_only' => 'This coupon is valid for first orders only.',
    'coupon_not_applicable' => 'Nothing in your cart qualifies for this discount.',
    'invalid_order_transition' => 'The order cannot change to that status.',
    'redirect_loop' => 'An unsafe redirect loop was detected and stopped.',
    'attribute_value_in_use' => 'This value is currently used by product variants. Remove that link first.',
    'attribute_value_used_in_images' => 'This value is currently used by product images. Remove that link first.',
    'attribute_is_variant_locked' => 'This attribute is used by product variants - its "variant" setting cannot be changed.',
    'product_missing_translation' => 'Name and slug must be set in both Arabic and English.',
    'product_missing_description' => 'The full description must be set in both Arabic and English.',
    'product_missing_category' => 'The product must be assigned to at least one category.',
    'product_missing_seo' => 'SEO title and description must be set in both languages.',
    'product_missing_variant' => 'The product must have at least one active variant.',
    'product_missing_primary_image' => 'The product must have a primary image.',
    'product_publish_not_allowed' => 'This product cannot be published yet - check the missing requirements.',
    'attribute_value_must_be_non_variant' => 'This value belongs to a variant-generating attribute (e.g. size or color) - those values are only set through product variants, not as a general product attribute.',
    'attribute_value_must_be_variant' => 'This value belongs to a non-variant attribute - it cannot be used as a product variant axis.',
    'attribute_value_must_be_color' => 'This value does not belong to a "color" attribute - it cannot be used to link an image to a color.',
    'variant_protected_stock' => 'This variant currently has physical stock.',
    'variant_protected_reserved' => 'This variant currently has an active reservation (in a customer cart).',
    'variant_protected_movements' => 'This variant has a stock movement history.',
    'variant_protected_sales' => 'This variant has been sold before.',
    'variant_deletion_blocked' => 'These variants cannot be removed - check the reasons for each.',
    'variant_matrix_conflict' => 'Someone else already modified some of these variants. Review the conflicting rows and reload.',
    'variant_matrix_limit_exceeded' => 'The requested combination count (:requested) exceeds the maximum allowed (:limit).',
    'variant_matrix_inconsistent' => 'An internal conflict occurred in some variants\' attribute set - nothing was saved. Please try again, and contact support if this keeps happening.',
    'product_image_temp_file_missing' => 'The uploaded file was not found - it may have expired. Please upload the image again.',
    'product_image_unreadable' => 'This file is not a valid image - try a different one.',
    'product_images_limit_exceeded' => 'You have reached the maximum number of images for this product (:limit images).',

];
