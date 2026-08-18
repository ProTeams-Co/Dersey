<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Batch 3.3 decision 2 - thrown by InventoryService::stocktake() when the
 * admin-entered count equals stock_quantity exactly (delta = 0). A
 * stocktake movement whose quantity is 0 would be a meaningless audit-log
 * row (InventoryMovementType::Adjust is supposed to mean "the count
 * changed something"), so nothing is written at all - render() follows
 * the same pattern as InsufficientStockException.
 */
class StocktakeNoChangeException extends Exception
{
    public function __construct(public readonly ProductVariant $variant)
    {
        parent::__construct(
            "Stocktake for variant #{$variant->id} matches the current stock_quantity exactly - nothing to record."
        );
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('errors.stocktake_no_change');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
