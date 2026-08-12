<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by ProductService whenever something tries to set status to
 * Published on a product that Product::canBePublished() says isn't ready -
 * the enforcement point is deliberately the service, not the controller or
 * the view, so there is exactly one place this rule can ever be bypassed
 * from (CLAUDE.md's own "لازم يتفرض في الـ service كمان" requirement).
 * render() follows the same pattern as CategoryHasDependentsException /
 * AttributeValueInUseException - left uncaught by AdminController on
 * purpose, renders its own translated 422/redirect.
 */
class ProductPublishBlockedException extends Exception
{
    public function __construct(public readonly Product $product)
    {
        parent::__construct(
            "Product #{$product->id} cannot be published: ".implode(', ', $product->publicationBlockers())
        );
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $blockers = array_map(fn (string $key) => __($key), $this->product->publicationBlockers());
        $message = __('errors.product_publish_not_allowed');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'blockers' => $blockers], 422);
        }

        return back()->with('error', $message);
    }
}
