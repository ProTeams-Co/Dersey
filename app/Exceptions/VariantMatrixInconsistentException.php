<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Batch 3.2-M Task 2 - the last-resort integrity backstop
 * ProductVariantMatrixService::assertConsistentAttributeSets() throws when,
 * after every migration step has run, the product's surviving variants
 * DON'T all share the exact same set of attribute_id's.
 *
 * This should never actually fire in normal operation - generateMatrix()'s
 * own steps are built to guarantee the invariant by construction (every
 * survivor gets the exact same new-attribute defaults, step 3 only fills
 * in genuinely missing combinations). It exists specifically because
 * ProductVariantValueObserver only validates one product_variant_values
 * row in isolation (is_variant, no duplicate attribute on THAT variant) -
 * it structurally cannot see "does this variant's finished option set
 * match its siblings", and syncOptionValues() (which normally does) is
 * deliberately NOT called during migration's default-value step (see that
 * step's own docblock for why). This is the check that would have caught
 * it if some future change broke that guarantee - thrown from inside the
 * same transaction, so it rolls back the entire migration, not just the
 * inconsistent row(s).
 */
class VariantMatrixInconsistentException extends Exception
{
    /**
     * @param  Collection<int, array{id: int, label: string, attribute_ids: list<int>}>  $variants
     */
    public function __construct(public readonly Collection $variants)
    {
        parent::__construct(
            'Variant matrix left inconsistent after migration: '.$variants->pluck('label')->implode(', ')
        );
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('errors.variant_matrix_inconsistent');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'variants' => $this->variants->values()], 422);
        }

        return back()->with('error', $message);
    }
}
