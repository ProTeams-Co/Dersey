<?php

namespace App\Exceptions;

use App\Models\Category;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by CategoryObserver::deleting() instead of a bare exception -
 * a bare exception has no render(), so Laravel's default handler turns it
 * into an unstyled 500 page in the admin instead of an actionable message.
 *
 * Named for "dependents" rather than "children" specifically: a category
 * can be blocked from deletion for two independent reasons - live child
 * categories, or products still directly assigned to it (approved as the
 * same "block outright" policy as children, not auto-unlink/auto-move) -
 * and both can apply to the same category at once, so the message must be
 * accurate to whichever reason(s) actually apply, not hardcode "children".
 */
class CategoryHasDependentsException extends Exception
{
    public function __construct(public readonly Category $category)
    {
        parent::__construct(
            "Category #{$category->id} cannot be deleted: ".implode(', ', $category->deletionBlockers())
        );
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = implode(' ', array_map(
            fn (string $key) => __($key),
            $this->category->deletionBlockers()
        ));

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
