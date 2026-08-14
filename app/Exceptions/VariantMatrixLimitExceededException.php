<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by ProductVariantMatrixService::generateMatrix() (and its
 * preview-only counterpart, previewMatrix()) when the requested attribute/
 * value selection's Cartesian product exceeds
 * ProductVariantMatrixService::MAX_COMBINATIONS (Batch 3.2-B decision 3 -
 * a named constant enforced in the service, not just a UI-side warning the
 * server would otherwise silently accept anyway).
 */
class VariantMatrixLimitExceededException extends Exception
{
    public function __construct(public readonly int $requested, public readonly int $limit)
    {
        parent::__construct("Requested {$requested} variant combinations, exceeding the limit of {$limit}.");
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('errors.variant_matrix_limit_exceeded', ['requested' => $this->requested, 'limit' => $this->limit]);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'requested' => $this->requested, 'limit' => $this->limit], 422);
        }

        return back()->with('error', $message);
    }
}
