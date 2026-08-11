<?php

use App\Enums\DiscountType;
use App\Enums\ShippingMethodType;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Services\Cart\CartService;
use App\Services\Shipping\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('waives shipping once the cart subtotal reaches the free_over threshold', function () {
    $zone = ShippingZone::factory()->create();
    $method = ShippingMethod::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingMethodType::FreeOver,
        'cost' => 5000,
        'free_over_amount' => 100000, // 1000 EGP
    ]);

    $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'price' => 60000]); // 600 EGP each

    $cartService = app(CartService::class);
    $shippingService = app(ShippingService::class);

    $cart = $cartService->findOrCreateForGuest('free-over-test-'.uniqid());
    $cartService->add($cart, $variant, 1); // subtotal 600 EGP - below threshold
    $cart->setRelation('items', $cart->items()->with('variant')->get());

    expect($shippingService->calculateCost($method, $cart)->minor())->toBe(5000);

    $cartService->add($cart, $variant, 1); // now 2 units - subtotal 1200 EGP - above threshold
    $cart->refresh();
    $cart->setRelation('items', $cart->items()->with('variant')->get());

    expect($shippingService->calculateCost($method, $cart)->minor())->toBe(0);
});

it('evaluates free_over against the subtotal AFTER a coupon discount, not the raw subtotal', function () {
    $zone = ShippingZone::factory()->create();
    $method = ShippingMethod::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingMethodType::FreeOver,
        'cost' => 5000,
        'free_over_amount' => 100000, // 1000 EGP
    ]);

    $coupon = Coupon::create([
        'code' => 'FREEOVER-DISCOUNT-TEST',
        'type' => DiscountType::Percent,
        'value' => 50, // 50% off
        'is_active' => true,
        'used_count' => 0,
        'first_order_only' => false,
    ]);

    $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'price' => 110000]); // 1100 EGP

    $cartService = app(CartService::class);
    $shippingService = app(ShippingService::class);

    $cart = $cartService->findOrCreateForGuest('free-over-discount-test-'.uniqid());
    $cartService->add($cart, $variant, 1); // raw subtotal 1100 EGP - above threshold on its own
    $cart->coupon_id = $coupon->id;
    $cart->save();
    $cart->setRelation('items', $cart->items()->with('variant')->get());
    $cart->setRelation('coupon', $coupon);

    // After the 50% coupon, subtotal is 550 EGP - below the 1000 EGP
    // free-shipping threshold, so shipping must NOT be free, even though
    // the raw (pre-discount) subtotal alone would have qualified.
    expect($shippingService->calculateCost($method, $cart)->minor())->toBe(5000);
});
