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
 *
 * Batch 3.2-C: deletionBlockers() can now return more than one reason at
 * once (used by a variant AND pictured in the gallery) - render() re-reads
 * it fresh (safe: this fires from `deleting()`, before anything is
 * actually removed) and joins every applicable translated reason, instead
 * of a single hardcoded message that only ever described the variant case.
 */
class AttributeValueInUseException extends Exception
{
    public function __construct(public readonly AttributeValue $value)
    {
        parent::__construct("Attribute value #{$value->id} cannot be deleted: still in use.");
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = collect($this->value->deletionBlockers())
            ->map(fn (string $key) => __($key))
            ->implode(' ');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
