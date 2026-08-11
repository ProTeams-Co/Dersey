<?php

namespace App\Services\Order;

use App\Enums\DiscountType;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Coupon\CouponService;
use App\Services\Inventory\InventoryService;
use App\Services\Shipping\ShippingService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly CouponService $couponService,
        private readonly ShippingService $shippingService,
    ) {
    }

    /**
     * The riskiest operation in the whole project: turns a Cart into a
     * permanent Order and actually removes stock from inventory. Every
     * step happens inside one transaction, so a failure at any point -
     * insufficient stock re-verified at the last second, a coupon that
     * just hit its limit, anything - leaves no trace at all: no order
     * row, no committed stock, nothing. The reservation CartService made
     * when items were added is not trusted on its own; stock is
     * re-checked here, under a lock, right before it's actually taken.
     *
     * @param  array{email?: ?string, phone?: ?string}  $guestInfo  ignored when $user is given
     */
    public function createFromCart(
        Cart $cart,
        Address $shippingAddress,
        ShippingMethod $shippingMethod,
        PaymentMethod $paymentMethod,
        ?User $user = null,
        array $guestInfo = [],
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $locale = null,
    ): Order {
        return DB::transaction(function () use (
            $cart, $shippingAddress, $shippingMethod, $paymentMethod, $user, $guestInfo, $ip, $userAgent, $locale
        ) {
            // Doesn't assume the caller already eager-loaded these -
            // loadMissing() only queries what isn't already there.
            $shippingAddress->loadMissing(['governorate', 'city']);

            // Eager-loads everything snapshotItem()/displayImage() will
            // read below - this is a fresh query (not the cart's
            // possibly-already-loaded `items` relation), so nothing here
            // is preloaded unless listed explicitly.
            $items = $cart->items()->with([
                'product.translations',
                'product.categories',
                'variant.attributeValues.attribute',
                'variant.attributeValues.translations',
                'variant.image',
                'variant.product.images',
            ])->get();

            // Cart::subtotal()/hasPriceChanges() read $this->items as a
            // property - this sets it explicitly to the collection just
            // fetched above (with its eager loads intact) instead of
            // letting those methods trigger a second, unoptimized query
            // (or a LazyLoadingViolationException, since a fresh
            // ->items()->get() call doesn't populate $cart's own relation
            // cache on its own).
            $cart->setRelation('items', $items);
            $cart->loadMissing('coupon');

            // Re-verify stock for real, under a lock, rather than trusting
            // the reservation CartService made when each item was added -
            // that reservation can be stale (another process could have
            // adjusted stock down since), and this is the last checkpoint
            // before stock actually leaves inventory.
            foreach ($items as $item) {
                $variant = ProductVariant::query()->lockForUpdate()->findOrFail($item->variant_id);

                if ($item->quantity > $variant->stock_quantity) {
                    throw new InsufficientStockException($variant, $item->quantity);
                }
            }

            // Presence alone ($cart->coupon !== null) is not enough - the
            // coupon could have expired, been deactivated, or dropped
            // under its min_order_amount between being applied to the
            // cart and checkout actually happening. Every place below
            // that reacts to "is there a coupon" gates on this instead
            // (discount, free shipping, and recordUsage() all shared the
            // same gap before this fix - not just the free_shipping
            // condition).
            $coupon = $cart->coupon;
            $couponIsValid = $coupon !== null && $this->couponService->validate($coupon, $cart, $user)->valid;

            $subtotal = $cart->subtotal();
            $discount = $couponIsValid ? $this->couponService->calculateDiscount($coupon, $cart) : Money::zero();
            $shippingCost = $couponIsValid && $coupon->type === DiscountType::FreeShipping
                ? Money::zero()
                : $this->shippingService->calculateCost($shippingMethod, $cart);
            $taxTotal = Money::zero(); // no tax logic specified in this batch
            $grandTotal = $subtotal->subtract($discount)->add($shippingCost)->add($taxTotal);

            $order = Order::create([
                // Placeholder to satisfy the NOT NULL + UNIQUE constraint
                // until the real, id-derived order_number is assigned
                // just below - see generateOrderNumber()'s docblock for
                // why this two-step approach, not max(id)+1.
                'order_number' => 'PENDING-'.Str::uuid(),
                'user_id' => $user?->id,
                'guest_email' => $user ? null : ($guestInfo['email'] ?? null),
                'guest_phone' => $user ? null : ($guestInfo['phone'] ?? null),
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'shipping_total' => $shippingCost,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                // Not recorded at all when the coupon turned out to be
                // invalid - an order snapshot showing a coupon_code with
                // discount_total = 0 would misrepresent what actually
                // happened at checkout.
                'coupon_id' => $couponIsValid ? $coupon->id : null,
                'coupon_code' => $couponIsValid ? $coupon->code : null,
                'currency' => 'EGP',
                'payment_method' => $paymentMethod,
                'shipping_address' => $this->snapshotAddress($shippingAddress),
                'billing_address' => null,
                'shipping_method_name' => $shippingMethod->getTranslations('name'),
                'locale' => $locale ?? app()->getLocale(),
                'ip' => $ip,
                'user_agent' => $userAgent,
                'placed_at' => now(),
            ]);

            $order->order_number = $this->generateOrderNumber($order);
            $order->save();

            foreach ($items as $item) {
                $this->snapshotItem($order, $item);
                // The variant's stock was already re-locked and verified
                // above; commit() re-locks it (cheap - InnoDB row locks
                // are reentrant within the same transaction) and is what
                // actually performs the write + logs the movement.
                $this->inventoryService->commit($item->variant, $item->quantity, $order);
            }

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrderStatus::Pending,
                'comment' => 'Order placed.',
            ]);

            if ($couponIsValid) {
                $this->couponService->recordUsage($coupon, $user, $order, $discount);
            }

            // Not CartService::clear() - clear() releases reservations,
            // but commit() above already consumed them (moved them from
            // "reserved" to "actually sold"). Releasing again would
            // incorrectly free reserved_quantity that's no longer there.
            $cart->items()->delete();
            $cart->delete();

            return $order->fresh(['items', 'statusHistories']);
        });
    }

    /**
     * @throws InvalidOrderTransitionException if the transition isn't legal per OrderStatus::canTransitionTo()
     */
    public function transitionTo(Order $order, OrderStatus $newStatus, ?string $comment = null, ?Model $changedBy = null): void
    {
        if (! $order->status->canTransitionTo($newStatus)) {
            throw new InvalidOrderTransitionException($order, $newStatus);
        }

        DB::transaction(function () use ($order, $newStatus, $comment, $changedBy) {
            $oldStatus = $order->status;

            $order->status = $newStatus;

            $timestampColumn = match ($newStatus) {
                OrderStatus::Shipped => 'shipped_at',
                OrderStatus::Delivered => 'delivered_at',
                OrderStatus::Cancelled => 'cancelled_at',
                default => null,
            };

            if ($timestampColumn !== null) {
                $order->{$timestampColumn} = now();
            }

            $order->save();

            $order->statusHistories()->create([
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'comment' => $comment,
                'changed_by_type' => $changedBy?->getMorphClass(),
                'changed_by_id' => $changedBy?->getKey(),
            ]);

            if ($newStatus === OrderStatus::Cancelled) {
                $this->restoreInventory($order);
            }
        });
    }

    public function cancel(Order $order, string $reason, ?Model $changedBy = null): void
    {
        $this->transitionTo($order, OrderStatus::Cancelled, $reason, $changedBy);
    }

    /**
     * order_number is derived from the order's own auto-increment `id`
     * AFTER insert, not computed beforehand from max(id) + 1 - MySQL's
     * AUTO_INCREMENT is already a safe, atomic primitive for uniqueness
     * under concurrency; max(id) + 1 (read-then-write in application
     * code) is exactly the race this replaces: two concurrent orders
     * reading the same max() would compute the same "next" number. No
     * separate sequence table or retry-on-collision loop is needed -
     * just trust the id the database already guaranteed is unique.
     */
    private function generateOrderNumber(Order $order): string
    {
        return sprintf('ORD-%s-%06d', now()->format('Y'), $order->id);
    }

    private function snapshotAddress(Address $address): array
    {
        return [
            'full_name' => $address->full_name,
            'phone' => $address->phone,
            'alt_phone' => $address->alt_phone,
            'governorate' => $address->governorate->getTranslations('name'),
            'city' => $address->city->getTranslations('name'),
            'street' => $address->street,
            'building' => $address->building,
            'floor' => $address->floor,
            'apartment' => $address->apartment,
            'landmark' => $address->landmark,
        ];
    }

    /**
     * Requires $item->product->translations and
     * $item->variant->attributeValues.{attribute,translations} to be
     * eager-loaded on the cart beforehand (preventLazyLoading throws
     * otherwise) - both locales are captured regardless of the current
     * request locale, since the order must display correctly however it's
     * viewed later.
     */
    private function snapshotItem(Order $order, CartItem $item): void
    {
        $variant = $item->variant;

        $productName = $item->product->translations
            ->mapWithKeys(fn ($translation) => [$translation->locale => $translation->name])
            ->all();

        $variantOptions = $variant->attributeValues->isNotEmpty()
            ? [
                'ar' => $variant->optionsLabel('ar'),
                'en' => $variant->optionsLabel('en'),
            ]
            : null;

        $order->items()->create([
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'product_name' => $productName,
            'variant_options' => $variantOptions,
            'sku' => $variant->sku,
            'image_path' => $variant->displayImage()?->path,
            'unit_price' => $item->unit_price,
            'quantity' => $item->quantity,
            'line_total' => $item->lineTotal(),
        ]);
    }

    private function restoreInventory(Order $order): void
    {
        // Explicit query, not the $order->items property - this method
        // doesn't control whether the caller eager-loaded `items`, and
        // preventLazyLoading only intercepts property access, not an
        // explicit ->items()->get() call.
        foreach ($order->items()->with('variant')->get() as $item) {
            if ($item->variant_id === null) {
                continue; // the variant itself is gone - nothing to restock
            }

            $this->inventoryService->adjust(
                $item->variant,
                $item->quantity,
                InventoryMovementType::In,
                $order,
                'Order cancelled - stock restored.'
            );
        }
    }
}
