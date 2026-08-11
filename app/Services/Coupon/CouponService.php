<?php

namespace App\Services\Coupon;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Exceptions\CouponLimitReachedException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Every rule a coupon must pass before it can be applied. Checked
     * again, under a row lock, in recordUsage() at actual order
     * confirmation - this method's result (read without a lock, at
     * "apply coupon" time) can be stale by the time checkout finishes.
     */
    public function validate(Coupon $coupon, Cart $cart, ?User $user): CouponValidationResult
    {
        if (! $coupon->is_active) {
            return CouponValidationResult::invalid('errors.coupon_inactive');
        }

        if ($coupon->hasNotStartedYet()) {
            return CouponValidationResult::invalid('errors.coupon_not_started');
        }

        if ($coupon->isExpired()) {
            return CouponValidationResult::invalid('errors.coupon_expired');
        }

        if ($coupon->hasReachedUsageLimit()) {
            return CouponValidationResult::invalid('errors.coupon_usage_limit_reached');
        }

        if ($user && $coupon->usage_limit_per_user !== null) {
            $userUsageCount = $coupon->usages()->where('user_id', $user->id)->count();

            if ($userUsageCount >= $coupon->usage_limit_per_user) {
                return CouponValidationResult::invalid('errors.coupon_user_limit_reached');
            }
        }

        if ($coupon->first_order_only && $user) {
            $hasPriorOrders = Order::query()
                ->where('user_id', $user->id)
                ->where('status', '!=', OrderStatus::Cancelled)
                ->exists();

            if ($hasPriorOrders) {
                return CouponValidationResult::invalid('errors.coupon_first_order_only');
            }
        }

        if ($coupon->min_order_amount !== null && $cart->subtotal()->minor() < $coupon->min_order_amount->minor()) {
            return CouponValidationResult::invalid('errors.coupon_min_order_not_met');
        }

        if ($coupon->isRestricted() && $this->qualifyingItems($coupon, $cart)->isEmpty()) {
            return CouponValidationResult::invalid('errors.coupon_not_applicable');
        }

        return CouponValidationResult::valid();
    }

    /**
     * Percentage discounts (and the Fixed cap) are computed off qualifying
     * items only when the coupon is restricted to specific
     * categories/products - a 20%-off-dresses coupon must not discount
     * the shoes in the same cart. Reads from already-loaded relations
     * (items.product.categories); eager-load before calling.
     */
    public function calculateDiscount(Coupon $coupon, Cart $cart): Money
    {
        if ($coupon->type === DiscountType::FreeShipping) {
            return Money::zero();
        }

        $qualifyingSubtotal = $this->qualifyingItems($coupon, $cart)->reduce(
            fn (Money $total, CartItem $item) => $total->add($item->lineTotal()),
            Money::zero()
        );

        if ($qualifyingSubtotal->isZero()) {
            return Money::zero();
        }

        $discount = match ($coupon->type) {
            DiscountType::Fixed => Money::fromMinor(min($coupon->value, $qualifyingSubtotal->minor())),
            DiscountType::Percent => $qualifyingSubtotal->percentage((float) $coupon->value),
            DiscountType::FreeShipping => Money::zero(),
        };

        // max_discount_amount is a strict cap, applied last, regardless of
        // how the discount above was computed.
        if ($coupon->max_discount_amount !== null && $discount->minor() > $coupon->max_discount_amount->minor()) {
            $discount = $coupon->max_discount_amount;
        }

        return $discount;
    }

    /**
     * Increments used_count and writes the CouponUsage row atomically,
     * under a row lock - approved approach for the used_count race
     * (100 concurrent redemptions of a 100-use coupon must not all
     * succeed). A concurrent recordUsage() call for the same coupon
     * blocks here until this transaction commits, then re-reads the
     * now-current used_count - so the limit check right before
     * incrementing is never working off a stale number.
     *
     * @throws CouponLimitReachedException if the limit was already reached under the lock
     */
    public function recordUsage(Coupon $coupon, ?User $user, Order $order, Money $discountAmount): CouponUsage
    {
        return DB::transaction(function () use ($coupon, $user, $order, $discountAmount) {
            $locked = Coupon::query()->lockForUpdate()->findOrFail($coupon->id);

            if ($locked->hasReachedUsageLimit()) {
                throw new CouponLimitReachedException($locked);
            }

            $locked->increment('used_count');

            return CouponUsage::create([
                'coupon_id' => $locked->id,
                'user_id' => $user?->id,
                'order_id' => $order->id,
                'discount_amount' => $discountAmount,
            ]);
        });
    }

    /**
     * @return Collection<int, CartItem>
     */
    private function qualifyingItems(Coupon $coupon, Cart $cart): Collection
    {
        if (! $coupon->isRestricted()) {
            return $cart->items;
        }

        $restrictedProductIds = $coupon->couponables()->where('couponable_type', Product::class)->pluck('couponable_id');
        $restrictedCategoryIds = $coupon->couponables()->where('couponable_type', Category::class)->pluck('couponable_id');

        return $cart->items->filter(function (CartItem $item) use ($restrictedProductIds, $restrictedCategoryIds) {
            if ($restrictedProductIds->contains($item->product_id)) {
                return true;
            }

            if ($restrictedCategoryIds->isEmpty()) {
                return false;
            }

            return $item->product->categories->pluck('id')->intersect($restrictedCategoryIds)->isNotEmpty();
        })->values();
    }
}
