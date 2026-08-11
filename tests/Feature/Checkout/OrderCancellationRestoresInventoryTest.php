<?php

use App\Enums\InventoryMovementType;
use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('restores the committed stock when an order is cancelled', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    $zone = ShippingZone::factory()->create();
    $address->governorate->update(['shipping_zone_id' => $zone->id]);
    $shippingMethod = ShippingMethod::factory()->create(['zone_id' => $zone->id]);

    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $cartService = app(CartService::class);
    $orderService = app(OrderService::class);

    $cart = $cartService->findOrCreateForUser($user);
    $cartService->add($cart, $variant, 3);

    $order = $orderService->createFromCart(
        cart: $cart->fresh(),
        shippingAddress: $address,
        shippingMethod: $shippingMethod,
        paymentMethod: PaymentMethod::CashOnDelivery,
        user: $user,
    );

    // commit() took 3 units out of stock (10 -> 7).
    expect($variant->fresh()->stock_quantity)->toBe(7);

    $orderService->cancel($order, 'Customer changed their mind.');

    expect($variant->fresh()->stock_quantity)->toBe(10)
        ->and($order->fresh()->status)->toBe(\App\Enums\OrderStatus::Cancelled)
        ->and($variant->movements()->where('type', InventoryMovementType::In)->exists())->toBeTrue();
});
