<?php

use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientStockException;
use App\Models\Address;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('leaves absolutely no trace when a step inside createFromCart() fails', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    $zone = ShippingZone::factory()->create();
    $address->governorate->update(['shipping_zone_id' => $zone->id]);
    $shippingMethod = ShippingMethod::factory()->create(['zone_id' => $zone->id]);

    $variantA = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $variantB = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $cartService = app(CartService::class);
    $orderService = app(OrderService::class);

    $cart = $cartService->findOrCreateForUser($user);
    $cartService->add($cart, $variantA, 1);
    $cartService->add($cart, $variantB, 1);

    $ordersBefore = Order::count();
    $movementsBefore = InventoryMovement::count();
    $stockABefore = $variantA->fresh()->stock_quantity;

    // Simulate another process draining variant B's stock between
    // reservation and checkout - createFromCart() must catch this at its
    // own re-verification step, not trust the earlier reservation.
    ProductVariant::where('id', $variantB->id)->update(['stock_quantity' => 0]);

    expect(fn () => $orderService->createFromCart(
        cart: $cart->fresh(),
        shippingAddress: $address,
        shippingMethod: $shippingMethod,
        paymentMethod: PaymentMethod::CashOnDelivery,
        user: $user,
    ))->toThrow(InsufficientStockException::class);

    // Nothing happened: no order, no movement, variant A (which was
    // perfectly fine) untouched, cart and its items still there.
    expect(Order::count())->toBe($ordersBefore)
        ->and(InventoryMovement::count())->toBe($movementsBefore)
        ->and($variantA->fresh()->stock_quantity)->toBe($stockABefore)
        ->and($cart->fresh())->not->toBeNull()
        ->and($cart->fresh()->items()->count())->toBe(2);
});
