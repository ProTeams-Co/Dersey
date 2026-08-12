<?php

namespace App\Exceptions;

use App\Models\AttributeValue;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by AttributeValueObserver::deleting() - unlike Category (which
 * has had canBeDeleted()/deletionBlockers() since Batch 2.2), AttributeValue
 * had no soft-delete restriction at all before Batch 3.1: only a
 * restrictOnDelete() FK on product_variant_values, which only fires on a
 * genuine forceDelete(), not the normal delete() an admin screen uses.
 * render() follows the same pattern as CategoryHasDependentsException.
 */
class AttributeValueInUseException extends Exception
{
    public function __construct(public readonly AttributeValue $value)
    {
        parent::__construct("Attribute value #{$value->id} cannot be deleted: still used by product variants.");
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('errors.attribute_value_in_use');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
