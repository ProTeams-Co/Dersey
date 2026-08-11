<?php

use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sums quantities of the same variant across both carts instead of replacing, capped at what is available', function () {
    $user = User::factory()->create();
    $variantShared = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $variantGuestOnly = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $cartService = app(CartService::class);

    $guestCart = $cartService->findOrCreateForGuest('guest-session-'.uniqid());
    $cartService->add($guestCart, $variantShared, 2);
    $cartService->add($guestCart, $variantGuestOnly, 1);

    $userCart = $cartService->findOrCreateForUser($user);
    $cartService->add($userCart, $variantShared, 3);

    $merged = $cartService->merge($guestCart, $userCart);

    $sharedItem = $merged->items->firstWhere('variant_id', $variantShared->id);
    $guestOnlyItem = $merged->items->firstWhere('variant_id', $variantGuestOnly->id);

    // 2 (guest) + 3 (user) = 5, not a replacement with either side's number alone.
    expect($sharedItem->quantity)->toBe(5)
        ->and($guestOnlyItem)->not->toBeNull()
        ->and($guestOnlyItem->quantity)->toBe(1)
        ->and(\App\Models\Cart::find($guestCart->id))->toBeNull(); // guest cart consumed
});

it('caps the merged quantity at what is actually available, never reserving more than exists', function () {
    $user = User::factory()->create();
    // Only 4 in stock total.
    $variant = ProductVariant::factory()->create(['stock_quantity' => 4]);

    $cartService = app(CartService::class);

    $guestCart = $cartService->findOrCreateForGuest('guest-session-'.uniqid());
    $cartService->add($guestCart, $variant, 2); // reserves 2, 2 left free

    $userCart = $cartService->findOrCreateForUser($user);
    $cartService->add($userCart, $variant, 2); // reserves 2, 0 left free

    // Combined request would be 4, which happens to exactly match total
    // stock here - available_quantity right before merging is 0 (all 4
    // already reserved by these two carts), so the merge can still only
    // add up to what free capacity exists after releasing the guest hold.
    $merged = $cartService->merge($guestCart, $userCart);

    $item = $merged->items->firstWhere('variant_id', $variant->id);

    expect($item->quantity)->toBe(4)
        ->and($variant->fresh()->reserved_quantity)->toBe(4)
        ->and($variant->fresh()->available_quantity)->toBe(0);
});
