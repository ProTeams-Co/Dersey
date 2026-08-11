<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use App\Models\Order;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by OrderService::transitionTo() when
 * OrderStatus::canTransitionTo() says the requested change isn't legal
 * (e.g. delivered -> pending). render() follows the same pattern as
 * CategoryHasDependentsException/InsufficientStockException.
 */
class InvalidOrderTransitionException extends Exception
{
    public function __construct(public readonly Order $order, public readonly OrderStatus $attempted)
    {
        parent::__construct(
            "Order #{$order->id} cannot transition from {$order->status->value} to {$attempted->value}."
        );
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('errors.invalid_order_transition');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
