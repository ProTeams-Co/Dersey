<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Thrown by ProductVariantMatrixService::updateVariants() when its
 * pre-write version check (every row's submitted `version` compared
 * against the row's actual current version, all in one query, before
 * anything is written) finds one or more mismatches - Batch 3.2-B decision
 * 4: App\Support\Traits\HasOptimisticLock's own StaleModelException has no
 * render() (deliberately, per its own docblock - it's an internal retry
 * signal, not something shown to a user), so this is the admin-facing
 * translation of that same conflict, catchable from the controller.
 *
 * Checking every row up front (rather than looping saveWithVersion() calls
 * and catching StaleModelException per row) means NOTHING is written when
 * any conflict exists - true all-or-nothing without relying on a
 * mid-transaction throw to trigger the rollback.
 */
class VariantMatrixConflictException extends Exception
{
    /**
     * @param  Collection<int, array{id: int, label: string}>  $conflictingVariants
     */
    public function __construct(public readonly Collection $conflictingVariants)
    {
        parent::__construct(
            'Variant(s) modified by another request: '.$conflictingVariants->pluck('label')->implode(', ')
        );
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('errors.variant_matrix_conflict');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'variants' => $this->conflictingVariants->values(),
            ], 409);
        }

        return back()->with('error', $message);
    }
}
