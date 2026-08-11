<?php

use App\Jobs\ReleaseExpiredCartsJob;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('leaves reserved_quantity equal to the merged quantity, not doubled, after merge()', function () {
    $user = User::factory()->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 20]);

    $cartService = app(CartService::class);

    $guestCart = $cartService->findOrCreateForGuest('reserved-consistency-guest-'.uniqid());
    $cartService->add($guestCart, $variant, 2);

    $userCart = $cartService->findOrCreateForUser($user);
    $cartService->add($userCart, $variant, 3);

    // Before merging: two independent reservations, 2 + 3 = 5.
    expect($variant->fresh()->reserved_quantity)->toBe(5);

    $merged = $cartService->merge($guestCart, $userCart);

    // After merging: one cart_item with quantity 5 - reserved_quantity
    // must still be 5, not 10 (the bug this test guards against would be
    // releasing 2, re-reserving 2, but never actually cancelling out the
    // guest's original 2 anywhere, or double-counting on top of the
    // user's original 3).
    expect($variant->fresh()->reserved_quantity)->toBe(5)
        ->and($merged->items->firstWhere('variant_id', $variant->id)->quantity)->toBe(5);
});

it('releases the reservation when an expired cart is cleaned up by the job', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $cartService = app(CartService::class);
    $cart = $cartService->findOrCreateForGuest('reserved-consistency-expiry-'.uniqid());
    $cartService->add($cart, $variant, 4);

    expect($variant->fresh()->reserved_quantity)->toBe(4);

    $cart->expires_at = now()->subDay();
    $cart->save();

    (new ReleaseExpiredCartsJob())->handle(app(CartService::class));

    expect($variant->fresh()->reserved_quantity)->toBe(0)
        ->and(Cart::find($cart->id))->toBeNull();
});

it('never leaves reserved_quantity out of sync with the actual sum of live cart_items - full consistency sweep', function () {
    $user = User::factory()->create();
    $variantA = ProductVariant::factory()->create(['stock_quantity' => 20]);
    $variantB = ProductVariant::factory()->create(['stock_quantity' => 20]);

    $cartService = app(CartService::class);

    // Exercises add(), update(), remove(), and merge() together - the
    // combination the leak was suspected to hide in.
    $guestCart = $cartService->findOrCreateForGuest('consistency-sweep-guest-'.uniqid());
    $cartService->add($guestCart, $variantA, 2);
    $cartService->add($guestCart, $variantB, 1);

    $userCart = $cartService->findOrCreateForUser($user);
    $cartService->add($userCart, $variantA, 3);

    $cartService->merge($guestCart, $userCart);

    $userItemA = $userCart->fresh()->items()->where('variant_id', $variantA->id)->first();
    $cartService->update($userItemA, 1); // reduce from merged 5 down to 1

    $userItemB = $userCart->fresh()->items()->where('variant_id', $variantB->id)->first();
    $cartService->remove($userItemB); // drop it entirely

    $orphans = DB::table('product_variants')
        ->select('product_variants.id', 'product_variants.reserved_quantity')
        ->whereRaw('product_variants.reserved_quantity != (
            select coalesce(sum(cart_items.quantity), 0)
            from cart_items
            inner join carts on carts.id = cart_items.cart_id
            where cart_items.variant_id = product_variants.id
        )')
        ->get();

    expect($orphans)->toHaveCount(0);
});
