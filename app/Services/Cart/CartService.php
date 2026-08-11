<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

/**
 * The spec's method signatures (add(variant, qty), clear()) leave out
 * which cart they act on - there's no implicit request/session-scoped
 * cart to resolve it from in this batch (no Controller exists yet), so
 * every method that needs one takes Cart explicitly, same reasoning as
 * InventoryService's methods taking the ProductVariant explicitly rather
 * than resolving "the current variant" from somewhere implicit.
 *
 * Cart lifetime: 1 day for a guest cart (session_id set, user_id null),
 * 7 days for a registered user's cart - your stated proposal, adopted
 * as-is. A guest's intent is much more likely to have evaporated by the
 * next day (no account to come back to), while a signed-in shopper
 * reasonably expects their cart to still be there after a weekend.
 */
class CartService
{
    private const GUEST_TTL_DAYS = 1;

    private const USER_TTL_DAYS = 7;

    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function findOrCreateForUser(User $user): Cart
    {
        $cart = Cart::query()->where('user_id', $user->id)->first();

        if ($cart) {
            $cart->expires_at = now()->addDays(self::USER_TTL_DAYS);
            $cart->save();

            return $cart;
        }

        return Cart::create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(self::USER_TTL_DAYS),
        ]);
    }

    public function findOrCreateForGuest(string $sessionId): Cart
    {
        $cart = Cart::query()->where('session_id', $sessionId)->first();

        if ($cart) {
            $cart->expires_at = now()->addDay();
            $cart->save();

            return $cart;
        }

        return Cart::create([
            'session_id' => $sessionId,
            'expires_at' => now()->addDays(self::GUEST_TTL_DAYS),
        ]);
    }

    /**
     * Adding a variant already in the cart increments its quantity
     * (delegates to update()) rather than erroring on the UNIQUE(cart_id,
     * variant_id) constraint.
     */
    public function add(Cart $cart, ProductVariant $variant, int $quantity): CartItem
    {
        return DB::transaction(function () use ($cart, $variant, $quantity) {
            $existing = $cart->items()->where('variant_id', $variant->id)->first();

            if ($existing) {
                return $this->update($existing, $existing->quantity + $quantity);
            }

            // final_price falls back to $this->product->base_price when
            // the variant has no override - doesn't assume the caller
            // already eager-loaded `product` on whatever variant instance
            // they happened to pass in (e.g. straight from route model
            // binding, which wouldn't).
            $variant->loadMissing('product');

            $this->inventoryService->reserve($variant, $quantity);

            return $cart->items()->create([
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'quantity' => $quantity,
                'unit_price' => $variant->final_price,
            ]);
        });
    }

    public function update(CartItem $item, int $quantity): CartItem
    {
        return DB::transaction(function () use ($item, $quantity) {
            $delta = $quantity - $item->quantity;

            if ($delta > 0) {
                $this->inventoryService->reserve($item->variant, $delta);
            } elseif ($delta < 0) {
                $this->inventoryService->release($item->variant, abs($delta));
            }

            $item->quantity = $quantity;
            $item->save();

            return $item;
        });
    }

    public function remove(CartItem $item): void
    {
        DB::transaction(function () use ($item) {
            $this->inventoryService->release($item->variant, $item->quantity);
            $item->delete();
        });
    }

    /**
     * Same variant in both carts: quantities are summed, capped at what's
     * actually available - never replaced (a guest cart with 2 of an item
     * merging into a user cart that already has 3 must not silently drop
     * back to 2). Guest items whose variant isn't already in the user
     * cart just move ownership; their existing reservation stays intact.
     */
    public function merge(Cart $guestCart, Cart $userCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $userCart) {
            foreach ($guestCart->items()->with('variant')->get() as $guestItem) {
                $userItem = $userCart->items()->with('variant')->where('variant_id', $guestItem->variant_id)->first();

                if ($userItem) {
                    // Release the guest's hold first - it becomes real
                    // free stock again, not just re-attributed, so the
                    // available_quantity check below reflects true capacity.
                    $this->inventoryService->release($guestItem->variant, $guestItem->quantity);

                    $variant = $guestItem->variant->fresh();
                    $additional = min($guestItem->quantity, $variant->available_quantity);

                    if ($additional > 0) {
                        $this->inventoryService->reserve($userItem->variant, $additional);
                        $userItem->quantity += $additional;
                        $userItem->save();
                    }

                    $guestItem->delete();
                } else {
                    $guestItem->cart_id = $userCart->id;
                    $guestItem->save();
                }
            }

            $guestCart->delete();

            return $userCart->fresh('items');
        });
    }

    public function clear(Cart $cart): void
    {
        DB::transaction(function () use ($cart) {
            foreach ($cart->items as $item) {
                $this->inventoryService->release($item->variant, $item->quantity);
            }

            $cart->items()->delete();
        });
    }
}
