<?php

namespace App\Services\Shipping;

use App\Enums\ShippingMethodType;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ShippingMethod;
use App\Services\Coupon\CouponService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;

class ShippingService
{
    public function __construct(private readonly CouponService $couponService)
    {
    }

    /**
     * Every active method in the zone the address's governorate belongs
     * to - a governorate with no zone assigned yet has no available
     * methods at all, rather than falling back to some default zone.
     *
     * @return Collection<int, ShippingMethod>
     */
    public function getAvailableMethods(Address $address, Cart $cart): Collection
    {
        $zoneId = $address->governorate->shipping_zone_id;

        if ($zoneId === null) {
            return new Collection();
        }

        return ShippingMethod::active()
            ->where('zone_id', $zoneId)
            ->orderBy('sort')
            ->get();
    }

    public function calculateCost(ShippingMethod $method, Cart $cart): Money
    {
        return match ($method->type) {
            ShippingMethodType::Flat => $method->cost,
            ShippingMethodType::FreeOver => $this->calculateFreeOverCost($method, $cart),
            ShippingMethodType::WeightBased => $this->calculateWeightBasedCost($method, $cart),
        };
    }

    /**
     * free_over is evaluated against the cart's subtotal AFTER any coupon
     * discount, not the raw subtotal - a customer who coupons their way
     * under the free-shipping threshold should not still get free
     * shipping on what they're actually paying.
     */
    private function calculateFreeOverCost(ShippingMethod $method, Cart $cart): Money
    {
        if ($method->free_over_amount === null) {
            return $method->cost;
        }

        $subtotalAfterDiscount = $this->subtotalAfterDiscount($cart);

        if ($subtotalAfterDiscount->minor() >= $method->free_over_amount->minor()) {
            return Money::zero();
        }

        return $method->cost;
    }

    /**
     * Reads from the already-loaded `items.product` relations; eager-load
     * before calling.
     */
    private function calculateWeightBasedCost(ShippingMethod $method, Cart $cart): Money
    {
        $totalGrams = $cart->items->sum(fn (CartItem $item) => $item->product->weight * $item->quantity);
        $totalKg = $totalGrams / 1000;

        $weightCost = $method->cost_per_kg !== null
            ? Money::fromMinor((int) round($method->cost_per_kg->minor() * $totalKg))
            : Money::zero();

        return $method->cost->add($weightCost);
    }

    private function subtotalAfterDiscount(Cart $cart): Money
    {
        $subtotal = $cart->subtotal();

        if ($cart->coupon === null) {
            return $subtotal;
        }

        return $subtotal->subtract($this->couponService->calculateDiscount($cart->coupon, $cart));
    }
}
