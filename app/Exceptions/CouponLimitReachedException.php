<?php

namespace App\Exceptions;

use App\Models\Coupon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by CouponService::recordUsage() when a coupon's usage_limit is
 * already reached under a row lock at confirmation time - the check that
 * actually matters, since Coupon::hasReachedUsageLimit() read earlier
 * (e.g. at cart-validation time) can be stale by the time the order is
 * actually placed. render() follows the same pattern as
 * CategoryHasDependentsException/InsufficientStockException.
 */
class CouponLimitReachedException extends Exception
{
    public function __construct(public readonly Coupon $coupon)
    {
        parent::__construct("Coupon #{$coupon->id} ({$coupon->code}) has reached its usage limit.");
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('errors.coupon_limit_reached');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
