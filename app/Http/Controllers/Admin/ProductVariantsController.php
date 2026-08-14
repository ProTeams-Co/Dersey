<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Rules\AttributeValueMustBeVariant;
use App\Services\Catalog\ProductVariantMatrixService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Stays thin (CLAUDE.md §17 / Batch 3.2-B Task 3) - every actual write
 * goes through App\Services\Catalog\ProductVariantMatrixService, this
 * class only validates + authorizes + calls it. Not an AdminController
 * subclass on purpose: that base class is for AdminTable-backed list/CRUD
 * screens (index/create/store/edit/update/destroy) - this is a handful of
 * custom actions against one product's variant matrix, a different shape
 * entirely.
 *
 * Route params are raw ids (findOrFail), matching ProductsController's own
 * convention, not route-model-binding.
 *
 * Every domain exception the service throws (VariantDeletionBlockedException,
 * VariantMatrixConflictException, VariantMatrixLimitExceededException) is
 * left uncaught here on purpose - each has its own render(), same
 * convention as ProductPublishBlockedException in ProductsController.
 */
class ProductVariantsController extends Controller
{
    use AuthorizesRequests;

    /**
     * Same override as AdminController::authorize() (CLAUDE.md §17) -
     * without it, the trait's default authorize() resolves the user from
     * the default `web` guard, which is always null on an admin request,
     * so every check here would silently deny everything. This class
     * doesn't extend AdminController (see class docblock), so it needs
     * its own copy rather than inheriting it.
     */
    public function authorize($ability, $arguments = [])
    {
        return $this->authorizeForUser(Auth::guard('admin')->user(), $ability, $arguments);
    }

    public function preview(Request $request, int|string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $valueIdsByAttribute = $this->validateAttributeSelection($request);

        $result = app(ProductVariantMatrixService::class)->previewMatrix($product, $valueIdsByAttribute);

        return response()->json($result);
    }

    public function generate(Request $request, int|string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $valueIdsByAttribute = $this->validateAttributeSelection($request);
        $defaultValues = $this->validateDefaultValues($request, $valueIdsByAttribute);

        $variants = app(ProductVariantMatrixService::class)->generateMatrix($product, $valueIdsByAttribute, $defaultValues);

        return response()->json([
            'message' => __('admin.products.variant_matrix_generated', ['count' => $variants->count()]),
            'count' => $variants->count(),
        ]);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id' => ['required', 'integer', 'exists:product_variants,id'],
            'rows.*.version' => ['required', 'integer', 'min:0'],
            'rows.*.sku' => ['required', 'string', 'max:255'],
            'rows.*.price' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'rows.*.compare_at_price' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'rows.*.is_active' => ['required', 'boolean'],
            'rows.*.initial_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->assertRowsBelongToProduct($product, $validated['rows']);
        $this->assertNoDuplicateSkus($validated['rows']);

        app(ProductVariantMatrixService::class)->updateVariants($product, $validated['rows']);

        return response()->json(['message' => __('admin.products.variant_matrix_saved')]);
    }

    public function toggle(Request $request, int|string $id, int|string $variantId): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $variant = ProductVariant::where('product_id', $product->id)->findOrFail($variantId);

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        app(ProductVariantMatrixService::class)->toggleVariant($variant, $data['is_active']);

        // toggleVariant() -> saveWithVersion() bumps the row's version on
        // the server - the client's copy of it (data-variant-version, used
        // by the bulk update()'s own optimistic-lock pre-check) needs to
        // move on too, or a later bulk save on the SAME row would look
        // like a conflict with itself.
        return response()->json(['message' => __('admin.crud.updated'), 'version' => $variant->fresh()->version]);
    }

    /**
     * Shared by preview()/generate() - `attributes` is an object keyed by
     * attribute id, each value a list of attribute_value_ids
     * (Task 2-a's per-attribute multi-select). Laravel's array validation
     * rules apply to VALUES at a dot path, not KEYS - AttributeValueMustBeVariant
     * on `attributes.*.*` covers every submitted value id, but the
     * attribute ids themselves (the array keys) need a separate check.
     *
     * @return array<int, list<int>>
     */
    private function validateAttributeSelection(Request $request): array
    {
        $data = $request->validate([
            'attributes' => ['required', 'array', 'min:1'],
            'attributes.*' => ['required', 'array', 'min:1'],
            'attributes.*.*' => ['required', 'integer', 'exists:attribute_values,id', new AttributeValueMustBeVariant],
        ]);

        $attributeIds = array_map('intval', array_keys($data['attributes']));
        $realVariantAttributeCount = Attribute::whereIn('id', $attributeIds)->where('is_variant', true)->count();

        if ($realVariantAttributeCount !== count($attributeIds)) {
            throw ValidationException::withMessages([
                'attributes' => [__('admin.products.variant_attribute_invalid')],
            ]);
        }

        return collect($data['attributes'])
            ->mapWithKeys(fn (array $values, string|int $attributeId) => [
                (int) $attributeId => array_values(array_map('intval', $values)),
            ])
            ->all();
    }

    /**
     * `default_values` (attribute_id => attribute_value_id) is only
     * required for attributes that are NEW relative to the product's
     * current variant axes - ProductVariantMatrixService::generateMatrix()
     * itself throws if one is missing, but validating it here first means
     * a missing default comes back as a normal 422 with a field-level
     * error instead of the service's InvalidArgumentException.
     *
     * @param  array<int, list<int>>  $valueIdsByAttribute
     * @return array<int, int>
     */
    private function validateDefaultValues(Request $request, array $valueIdsByAttribute): array
    {
        $data = $request->validate([
            'default_values' => ['sometimes', 'array'],
            'default_values.*' => ['integer', 'exists:attribute_values,id', new AttributeValueMustBeVariant],
        ]);

        $defaultValues = collect($data['default_values'] ?? [])
            ->mapWithKeys(fn (mixed $valueId, string|int $attributeId) => [(int) $attributeId => (int) $valueId])
            ->all();

        foreach ($defaultValues as $attributeId => $valueId) {
            if (! in_array($valueId, $valueIdsByAttribute[$attributeId] ?? [], true)) {
                throw ValidationException::withMessages([
                    'default_values' => [__('admin.products.variant_default_value_invalid')],
                ]);
            }
        }

        return $defaultValues;
    }

    /**
     * @param  list<array{id: int}>  $rows
     */
    private function assertRowsBelongToProduct(Product $product, array $rows): void
    {
        $ids = array_column($rows, 'id');
        $ownedCount = ProductVariant::where('product_id', $product->id)->whereIn('id', $ids)->count();

        if ($ownedCount !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'rows' => [__('admin.products.variant_row_mismatch')],
            ]);
        }
    }

    /**
     * SKU uniqueness needs a per-row "ignore my own id" check - Rule::unique()->ignore()
     * only supports one static id, not one per array row, so this runs as
     * its own pass instead of a validate() rule.
     *
     * @param  list<array{id: int, sku: string}>  $rows
     */
    private function assertNoDuplicateSkus(array $rows): void
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            $taken = ProductVariant::where('sku', $row['sku'])->where('id', '!=', $row['id'])->exists();

            if ($taken) {
                $errors["rows.{$index}.sku"] = [__('admin.products.variant_sku_taken')];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
