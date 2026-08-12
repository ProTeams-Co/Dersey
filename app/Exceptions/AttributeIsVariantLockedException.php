<?php

namespace App\Exceptions;

use App\Models\Attribute;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by AttributeObserver::updating() when `is_variant` is being
 * changed on an attribute that already has values in use by product
 * variants - flipping it would silently invalidate
 * ProductVariantValueObserver's own is_variant check for every existing
 * variant using one of this attribute's values.
 */
class AttributeIsVariantLockedException extends Exception
{
    public function __construct(public readonly Attribute $attribute)
    {
        parent::__construct("Attribute #{$attribute->id}'s is_variant flag cannot change: values are in use by product variants.");
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('errors.attribute_is_variant_locked');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
