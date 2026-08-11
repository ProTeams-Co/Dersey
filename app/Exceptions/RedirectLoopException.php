<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by RedirectService::resolve() when a chain of redirects revisits a
 * path it already passed through (e.g. A -> B -> A) - stops the chain
 * instead of looping forever. render() follows the same pattern as
 * CategoryHasDependentsException/InvalidOrderTransitionException.
 */
class RedirectLoopException extends Exception
{
    public function __construct(public readonly string $path)
    {
        parent::__construct("Redirect loop detected while resolving \"{$path}\".");
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('errors.redirect_loop');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 500);
        }

        return back()->with('error', $message);
    }
}
