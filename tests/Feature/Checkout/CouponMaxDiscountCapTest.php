<?php

use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use App\Services\Coupon\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('caps a percentage discount at max_discount_amount even when the percentage would compute higher', function () {
    $coupon = Coupon::create([
        'code' => 'CAPPED-PERCENT',
        'type' => DiscountType::Percent,
        'value' => 50, // 50% off
        'max_discount_amount' => 10000, // capped at 100 EGP
        'is_active' => true,
        'used_count' => 0,
        'first_order_only' => false,
    ]);

    // 50% of 1000 EGP would be 500 EGP - far more than the 100 EGP cap.
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'price' => 100000]);

    $cartService = app(CartService::class);
    $couponService = app(CouponService::class);

    $cart = $cartService->findOrCreateForGuest('max-discount-test-'.uniqid());
    $cartService->add($cart, $variant, 1);
    $cart->setRelation('items', $cart->items()->with(['product.categories', 'variant'])->get());

    $discount = $couponService->calculateDiscount($coupon, $cart);

    expect($discount->minor())->toBe(10000); // capped, not 50000
});

it('does not cap a discount that is already below max_discount_amount', function () {
    $coupon = Coupon::create([
        'code' => 'UNCAPPED-PERCENT',
        'type' => DiscountType::Percent,
        'value' => 10, // 10% off
        'max_discount_amount' => 50000, // cap far above what 10% would produce
        'is_active' => true,
        'used_count' => 0,
        'first_order_only' => false,
    ]);

    $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'price' => 100000]); // 1000 EGP

    $cartService = app(CartService::class);
    $couponService = app(CouponService::class);

    $cart = $cartService->findOrCreateForGuest('uncapped-discount-test-'.uniqid());
    $cartService->add($cart, $variant, 1);
    $cart->setRelation('items', $cart->items()->with(['product.categories', 'variant'])->get());

    $discount = $couponService->calculateDiscount($coupon, $cart);

    expect($discount->minor())->toBe(10000); // 10% of 1000 EGP = 100 EGP, well under the cap
});
