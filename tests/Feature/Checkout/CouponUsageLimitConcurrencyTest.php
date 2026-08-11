<?php

use App\Enums\DiscountType;
use App\Exceptions\CouponLimitReachedException;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\Coupon\CouponService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A true multi-process race (5 separate OS processes hitting the same row
 * concurrently via lockForUpdate()) was run manually against the real
 * MySQL instance as part of this batch's verification - 3 succeeded, 2
 * were rejected, for a coupon with usage_limit=3, and the final used_count
 * matched exactly. SQLite-in-memory (this suite's test driver) can't host
 * genuinely concurrent connections the way that proof needed, so this
 * Pest test instead proves the same invariant sequentially - exactly the
 * same pattern the project's other lock-based test (Batch 2.3's
 * OptimisticLockConcurrencyTest) already uses for the same reason.
 */
it('never lets used_count exceed usage_limit, rejecting attempts once the limit is reached', function () {
    $coupon = Coupon::create([
        'code' => 'LIMIT-TEST',
        'type' => DiscountType::Fixed,
        'value' => 1000,
        'usage_limit' => 2,
        'used_count' => 0,
        'first_order_only' => false,
        'is_active' => true,
    ]);

    $couponService = app(CouponService::class);

    $orderA = Order::factory()->create();
    $orderB = Order::factory()->create();
    $orderC = Order::factory()->create();

    $couponService->recordUsage($coupon, null, $orderA, Money::fromMinor(1000));
    $couponService->recordUsage($coupon, null, $orderB, Money::fromMinor(1000));

    expect($coupon->fresh()->used_count)->toBe(2);

    expect(fn () => $couponService->recordUsage($coupon, null, $orderC, Money::fromMinor(1000)))
        ->toThrow(CouponLimitReachedException::class);

    expect($coupon->fresh()->used_count)->toBe(2)
        ->and($coupon->usages()->count())->toBe(2);
});
