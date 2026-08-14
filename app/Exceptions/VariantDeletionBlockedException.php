<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Thrown by ProductVariantMatrixService::generateMatrix() when reconciling
 * the desired attribute/value set against existing variants would remove
 * one or more ProductVariant::isProtected() rows (Batch 3.2-B decisions
 * 1/2) - removing an attribute or a value from the variant axes never
 * force-deletes anything, but it also never silently drops a variant that
 * has real stock, a reservation, movement history, or sales.
 *
 * $blockedVariants carries enough to build both the message and a
 * per-variant reason list, keyed by variant id - the JSON response needs
 * to name every blocking variant, not just report "something is blocked".
 */
class VariantDeletionBlockedException extends Exception
{
    /**
     * @param  Collection<int, array{id: int, label: string, reasons: list<string>}>  $blockedVariants
     */
    public function __construct(public readonly Collection $blockedVariants)
    {
        parent::__construct(
            'Cannot remove variant(s): '.$blockedVariants->pluck('label')->implode(', ')
        );
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $variants = $this->blockedVariants->map(fn (array $row) => [
            'id' => $row['id'],
            'label' => $row['label'],
            'reasons' => array_map(fn (string $key) => __($key), $row['reasons']),
        ])->values();

        $message = __('errors.variant_deletion_blocked');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'variants' => $variants], 422);
        }

        return back()->with('error', $message);
    }
}
