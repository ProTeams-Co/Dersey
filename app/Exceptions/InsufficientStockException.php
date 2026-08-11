<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by InventoryService whenever a requested quantity would push
 * stock_quantity below zero, or a reservation would exceed
 * available_quantity. render() follows the same pattern as
 * CategoryHasDependentsException (Batch 2.2): JSON 422 for AJAX/API,
 * back()->with('error', ...) otherwise, message from a translation key
 * rather than raw text.
 */
class InsufficientStockException extends Exception
{
    public function __construct(public readonly ProductVariant $variant, public readonly int $requested)
    {
        parent::__construct(
            "Variant #{$variant->id} cannot fulfill a request for {$requested} unit(s) - insufficient stock."
        );
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('errors.insufficient_stock');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
