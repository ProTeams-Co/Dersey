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

];
