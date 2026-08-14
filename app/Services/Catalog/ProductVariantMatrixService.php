<?php

namespace App\Services\Catalog;

use App\Enums\InventoryMovementType;
use App\Exceptions\VariantDeletionBlockedException;
use App\Exceptions\VariantMatrixConflictException;
use App\Exceptions\VariantMatrixInconsistentException;
use App\Exceptions\VariantMatrixLimitExceededException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Batch 3.2-B's own service, separate from App\Services\Catalog\ProductService -
 * that service's docblock scopes itself explicitly to translations/
 * categories/non-variant attributes/SEO; the variant matrix (Cartesian
 * generation, migration-not-replacement, optimistic-locked bulk save) is a
 * large, structurally different domain that would only bloat it.
 *
 * Deliberately does NOT delegate to Product::generateVariants() for the
 * general case: that method always uses an attribute's FULL value set
 * (`$attribute->values->pluck('id')`), with no way to pass a chosen
 * subset - but Task 2's own UI spec requires per-attribute multi-select of
 * VALUES, not just attributes. Reinventing generateVariants() would mean
 * duplicating its Cartesian-product math and its variant-creation
 * mechanics with different logic - instead, this service builds
 * combinations from the caller-given value subsets itself, but every
 * actual write still goes through the exact same two primitives
 * generateVariants() itself uses: $product->variants()->create([...]) and
 * ProductVariant::syncOptionValues() (which is what actually enforces the
 * three invariants - is_variant, no duplicate attribute per variant, same
 * attribute set across siblings).
 */
class ProductVariantMatrixService
{
    /**
     * A named, enforced constant (Batch 3.2-B decision 3) - the UI warns
     * before this, but the service is the actual gate; previewMatrix() and
     * generateMatrix() both check it the same way.
     */
    public const MAX_COMBINATIONS = 200;

    /**
     * Computes what generateMatrix() would do without writing anything -
     * the live "N variants will be generated" preview Task 2-a requires.
     *
     * The "new" count doesn't need to know WHICH default value would be
     * chosen for a newly-added attribute to be correct: every surviving
     * variant, once migrated (old values + one default per new
     * attribute), lands on exactly one specific point in the full desired
     * Cartesian space - so surviving variants always account for exactly
     * `kept` of the `total` desired combinations, regardless of which
     * default is picked, and the rest is always `total - kept`.
     *
     * @param  array<int, list<int>>  $valueIdsByAttribute  attribute_id => [attribute_value_id, ...]
     * @return array{total: int, new: int, kept: int, removed: int, removed_protected: list<array{id: int, label: string, reasons: list<string>}>}
     */
    public function previewMatrix(Product $product, array $valueIdsByAttribute): array
    {
        $this->assertWithinLimit($valueIdsByAttribute);

        // .translations is required by optionsLabel() below (called on any
        // blocked/removed variant) - HasTranslations::translate() reads
        // from an already-loaded relation only, never lazy-loads (see
        // ProductVariant::optionsLabel()'s own docblock). Missing here
        // meant a real 500 the moment any protected variant was actually
        // reported - caught via a live browser session's error log, not
        // by the mandatory test suite (which called generateMatrix()'s
        // exception path directly, not through previewMatrix()).
        $existingVariants = $product->variants()->with(['attributeValues.attribute', 'attributeValues.translations'])->get();
        $currentAttributeIds = $this->currentAttributeIds($existingVariants);
        $removedAttributeIds = array_values(array_diff($currentAttributeIds, array_keys($valueIdsByAttribute)));

        $toRemove = $this->variantsToRemove($existingVariants, $valueIdsByAttribute, $removedAttributeIds);
        $keptCount = $existingVariants->count() - $toRemove->count();
        $total = count($this->cartesian($valueIdsByAttribute));

        $toRemove->loadCount(['movements', 'orderItems']);
        $blocked = $toRemove->filter(fn (ProductVariant $variant) => $variant->isProtected());

        return [
            'total' => $total,
            'new' => max(0, $total - $keptCount),
            'kept' => $keptCount,
            'removed' => $toRemove->count(),
            'removed_protected' => $blocked->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'label' => $variant->optionsLabel('ar'),
                'reasons' => $variant->protectionReasons(),
            ])->values()->all(),
        ];
    }

    /**
     * The real write. Reconciles $product's existing variants against the
     * desired attribute/value selection in one transaction:
     *   1. Removed attributes/values -> variants that no longer have a
     *      valid combination. Every one of them is checked for
     *      ProductVariant::isProtected() in a single query
     *      (loadCount(['movements', 'orderItems'])) before anything is
     *      touched - if ANY is protected, VariantDeletionBlockedException
     *      (422) aborts the whole call, nothing is removed. Unprotected
     *      ones are soft-deleted - never forceDelete().
     *   2. Newly added attributes -> $defaultValueIdByNewAttribute's value
     *      is appended to every surviving variant via syncOptionValues()
     *      (preserves the variant's own id - only its option pivot rows
     *      change).
     *   3. Whatever combinations are still missing after (1)/(2) get
     *      created fresh via $product->variants()->create() +
     *      syncOptionValues() - new variant, new id, stock starts at the
     *      column default (0), exactly like Product::generateVariants().
     *
     * No existing variant's id is ever lost by this method - only removed
     * ones (already checked unprotected) and the ones this call itself
     * just created.
     *
     * @param  array<int, list<int>>  $valueIdsByAttribute
     * @param  array<int, int>  $defaultValueIdByNewAttribute  attribute_id => attribute_value_id, required for every attribute_id in $valueIdsByAttribute that isn't already one of the product's current variant axes
     * @return Collection<int, ProductVariant>
     */
    public function generateMatrix(Product $product, array $valueIdsByAttribute, array $defaultValueIdByNewAttribute = []): Collection
    {
        $this->assertWithinLimit($valueIdsByAttribute);

        return DB::transaction(function () use ($product, $valueIdsByAttribute, $defaultValueIdByNewAttribute) {
            // .translations is required by optionsLabel() (called on any
            // blocked variant when building VariantDeletionBlockedException,
            // and on every surviving/created variant in the returned
            // Collection) - see the identical fix + explanation in
            // previewMatrix() above.
            $existingVariants = $product->variants()->with(['attributeValues.attribute', 'attributeValues.translations'])->get();
            // ->with() doesn't hydrate the inverse `product` relation the
            // way $product->variants()->create() automatically does -
            // syncOptionValues() (step 2 below) reads $this->product, which
            // would otherwise trigger a forbidden lazy load (preventLazyLoading()
            // is on outside production). Reusing the already-in-memory
            // $product avoids both the violation and a wasted query.
            $existingVariants->each(fn (ProductVariant $variant) => $variant->setRelation('product', $product));
            $currentAttributeIds = $this->currentAttributeIds($existingVariants);
            $desiredAttributeIds = array_keys($valueIdsByAttribute);
            $newAttributeIds = array_values(array_diff($desiredAttributeIds, $currentAttributeIds));
            $removedAttributeIds = array_values(array_diff($currentAttributeIds, $desiredAttributeIds));

            // A default value is only meaningful (and required) when there
            // are existing variants to migrate forward - a pure from-scratch
            // generation (no variants at all yet) has nothing to apply a
            // default to, even though every desired attribute is technically
            // "new" relative to the empty current axis set.
            if ($existingVariants->isNotEmpty()) {
                foreach ($newAttributeIds as $attributeId) {
                    if (! isset($defaultValueIdByNewAttribute[$attributeId])) {
                        throw new InvalidArgumentException(
                            "A default value is required for newly added attribute #{$attributeId}."
                        );
                    }
                }
            }

            // Step 1: removed attributes/values -> variants that no longer
            // have a valid combination under the desired selection.
            $toRemove = $this->variantsToRemove($existingVariants, $valueIdsByAttribute, $removedAttributeIds);

            if ($toRemove->isNotEmpty()) {
                $toRemove->loadCount(['movements', 'orderItems']);
                $blocked = $toRemove->filter(fn (ProductVariant $variant) => $variant->isProtected());

                if ($blocked->isNotEmpty()) {
                    throw new VariantDeletionBlockedException(
                        $blocked->map(fn (ProductVariant $variant) => [
                            'id' => $variant->id,
                            'label' => $variant->optionsLabel('ar'),
                            'reasons' => $variant->protectionReasons(),
                        ])->values()
                    );
                }

                foreach ($toRemove as $variant) {
                    $variant->delete();
                }
            }

            $surviving = $existingVariants->diff($toRemove);

            // Step 2: apply every new attribute's default value to every
            // surviving variant. Deliberately NOT syncOptionValues() here -
            // that method's own same-attribute-set-as-siblings check reads
            // A SINGLE "sample sibling" from the database on every call, so
            // updating survivors one at a time would make it compare an
            // already-migrated variant against a not-yet-migrated one and
            // throw a false-positive mismatch mid-loop, even though the
            // final state (every survivor gets the exact same new value)
            // provably satisfies the invariant by construction. A direct,
            // targeted variantValues()->create() still goes through
            // ProductVariantValueObserver's per-row checks (is_variant,
            // no duplicate attribute on this variant) - it just skips the
            // cross-sibling comparison that doesn't apply to this
            // deterministic path.
            if ($newAttributeIds !== []) {
                foreach ($surviving as $variant) {
                    foreach ($newAttributeIds as $attributeId) {
                        $variant->variantValues()->create(['attribute_value_id' => $defaultValueIdByNewAttribute[$attributeId]]);
                    }

                    $variant->unsetRelation('attributeValues');
                }
            }

            // Step 3: create whatever combinations are still missing.
            $existingKeys = $surviving->map(
                fn (ProductVariant $v) => $this->combinationKey($v->fresh(['attributeValues'])->attributeValues->pluck('id')->all())
            )->all();

            $created = new Collection;

            foreach ($this->cartesian($valueIdsByAttribute) as $combination) {
                if (in_array($this->combinationKey($combination), $existingKeys, true)) {
                    continue;
                }

                $variant = $product->variants()->create([
                    'sku' => $this->generateSku($product, $combination),
                    'stock_quantity' => 0,
                    'low_stock_threshold' => 5,
                ]);

                // `version` is deliberately NOT fillable (only
                // HasOptimisticLock's own saveWithVersion() is meant to
                // move it) - a direct property assignment bypasses mass-
                // assignment protection without touching $fillable, and is
                // needed here for the same reason as the comment on
                // low_stock_threshold above: create() only populates the
                // in-memory model with what was actually passed in, so a
                // caller reading ->version right after this (without a
                // refresh()) would otherwise get null instead of the
                // column's real default (0).
                $variant->version = 0;

                $variant->syncOptionValues($combination);
                $created->push($variant);
            }

            // Task 2's own last-resort backstop - still inside this same
            // transaction, so a thrown VariantMatrixInconsistentException
            // rolls back everything above (steps 1-3), not just whatever
            // row exposed the inconsistency.
            $this->assertConsistentAttributeSets($product);

            return $surviving->merge($created)->values();
        });
    }

    /**
     * Verifies every surviving (non-trashed) variant of $product shares
     * the exact same set of attribute_id's - the invariant
     * ProductVariantValueObserver structurally cannot check itself (it
     * only ever sees one product_variant_values row in isolation, never a
     * variant's whole finished option set against its siblings), and that
     * generateMatrix()'s migration step deliberately doesn't re-verify via
     * syncOptionValues() either (see that step's own docblock for why).
     *
     * One query, not one per variant - every (variant_id, attribute_id)
     * pair for the whole product is fetched at once and grouped in PHP,
     * not via a driver-specific SQL aggregate (MySQL's GROUP_CONCAT
     * supports an ORDER BY clause SQLite's doesn't - grouping in PHP
     * instead sidesteps that portability gap entirely, matching this
     * project's own "verify on both drivers, don't assume" convention).
     */
    private function assertConsistentAttributeSets(Product $product): void
    {
        $rows = DB::table('product_variant_values')
            ->join('product_variants', 'product_variants.id', '=', 'product_variant_values.variant_id')
            ->join('attribute_values', 'attribute_values.id', '=', 'product_variant_values.attribute_value_id')
            ->where('product_variants.product_id', $product->id)
            ->whereNull('product_variants.deleted_at')
            ->select('product_variant_values.variant_id', 'attribute_values.attribute_id')
            ->get();

        $attributeIdsByVariant = $rows->groupBy('variant_id')
            ->map(fn (Collection $group) => $group->pluck('attribute_id')->unique()->sort()->values());

        $distinctSets = $attributeIdsByVariant->map(fn (Collection $ids) => $ids->implode(','))->unique();

        if ($distinctSets->count() <= 1) {
            return;
        }

        $variantIds = $attributeIdsByVariant->keys();
        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->with(['attributeValues.attribute', 'attributeValues.translations'])
            ->get()
            ->keyBy('id');

        $conflicting = $attributeIdsByVariant->map(fn (Collection $ids, int $variantId) => [
            'id' => $variantId,
            'label' => $variants->get($variantId)?->optionsLabel('ar') ?? "#{$variantId}",
            'attribute_ids' => $ids->values()->all(),
        ])->values();

        throw new VariantMatrixInconsistentException($conflicting);
    }

    /**
     * Bulk-saves the matrix table's editable columns (sku, price,
     * compare_at_price, is_active) plus an optional one-time initial stock
     * amount per row, all in one transaction.
     *
     * Optimistic locking: every submitted row's `version` is compared
     * against the row's ACTUAL current version in ONE query, before
     * anything is written at all - not a per-row saveWithVersion() call
     * that throws on the first mismatch mid-loop. This is what makes
     * "collect every conflicting row's name, not just the first" and
     * "nothing written if any conflict exists" both true at once, without
     * relying on a mid-transaction exception to trigger the rollback.
     *
     * stock_quantity is never touched here (Batch 3.2-B decision 1 - this
     * screen is read-only for existing stock) - `initial_stock` only ever
     * applies through App\Services\Inventory\InventoryService::adjust(),
     * and only for a variant that has never had a movement recorded yet
     * (guards against re-applying "initial" stock on a later, unrelated
     * save of the same row).
     *
     * @param  list<array{id: int, version: int, sku: string, price: ?string, compare_at_price: ?string, is_active: bool, initial_stock: ?int}>  $rows
     */
    public function updateVariants(Product $product, array $rows): void
    {
        $ids = array_column($rows, 'id');
        $variants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereIn('id', $ids)
            // optionsLabel() (used below when reporting a version conflict)
            // reads attributeValues.attribute/translations from an
            // already-loaded relation only - without this, a genuine
            // conflict would throw LazyLoadingViolationException instead
            // of the 409 it's supposed to report.
            ->with(['attributeValues.attribute', 'attributeValues.translations'])
            ->get()
            ->keyBy('id');

        $conflicts = new Collection;

        foreach ($rows as $row) {
            $variant = $variants->get($row['id']);

            if ($variant && (int) $variant->version !== (int) $row['version']) {
                $conflicts->push(['id' => $variant->id, 'label' => $variant->optionsLabel('ar')]);
            }
        }

        if ($conflicts->isNotEmpty()) {
            throw new VariantMatrixConflictException($conflicts);
        }

        DB::transaction(function () use ($rows, $variants) {
            foreach ($rows as $row) {
                $variant = $variants->get($row['id']);

                if (! $variant) {
                    continue;
                }

                $variant->fill([
                    'sku' => $row['sku'],
                    // Batch 3.2-M: MoneyCast::set() now REJECTS a raw
                    // string outright (it used to silently (int)-truncate
                    // "199.50" to 199 piasters) - Money::fromMajorNullable()
                    // is the one shared place this major-unit-string-to-
                    // Money conversion happens, same helper
                    // ProductsController::convertPriceFields() uses.
                    'price' => Money::fromMajorNullable($row['price']),
                    'compare_at_price' => Money::fromMajorNullable($row['compare_at_price'] ?? null),
                    'is_active' => $row['is_active'],
                ]);
                $variant->saveWithVersion();

                $initialStock = (int) ($row['initial_stock'] ?? 0);

                if ($initialStock > 0 && $variant->movements()->doesntExist()) {
                    app(InventoryService::class)->adjust(
                        $variant,
                        $initialStock,
                        InventoryMovementType::In,
                        $variant,
                        __('admin.products.variant_initial_stock_note'),
                    );
                }
            }
        });
    }

    public function toggleVariant(ProductVariant $variant, bool $active): void
    {
        $variant->is_active = $active;
        $variant->saveWithVersion();
    }

    /**
     * @param  array<int, list<int>>  $valueIdsByAttribute
     */
    private function assertWithinLimit(array $valueIdsByAttribute): void
    {
        $total = $valueIdsByAttribute === [] ? 0 : array_product(array_map('count', $valueIdsByAttribute));

        if ($total > self::MAX_COMBINATIONS) {
            throw new VariantMatrixLimitExceededException($total, self::MAX_COMBINATIONS);
        }
    }

    /**
     * @return list<int>
     */
    private function currentAttributeIds(Collection $existingVariants): array
    {
        if ($existingVariants->isEmpty()) {
            return [];
        }

        return $existingVariants->first()->attributeValues->pluck('attribute_id')->unique()->values()->all();
    }

    /**
     * Existing variants that no longer have a valid combination under the
     * desired selection - either because one of their values belongs to a
     * removed attribute, or because a kept attribute's value they use is
     * no longer in the desired value subset for it.
     *
     * @param  array<int, list<int>>  $valueIdsByAttribute
     * @param  list<int>  $removedAttributeIds
     * @return Collection<int, ProductVariant>
     */
    private function variantsToRemove(Collection $existingVariants, array $valueIdsByAttribute, array $removedAttributeIds): Collection
    {
        return $existingVariants->filter(function (ProductVariant $variant) use ($valueIdsByAttribute, $removedAttributeIds) {
            foreach ($variant->attributeValues as $value) {
                if (in_array($value->attribute_id, $removedAttributeIds, true)) {
                    return true;
                }

                if (isset($valueIdsByAttribute[$value->attribute_id])
                    && ! in_array($value->id, $valueIdsByAttribute[$value->attribute_id], true)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /**
     * Cartesian product of every attribute's chosen value list.
     *
     * @param  array<int, list<int>>  $valueIdsByAttribute
     * @return list<list<int>>
     */
    private function cartesian(array $valueIdsByAttribute): array
    {
        return array_reduce($valueIdsByAttribute, function (array $carry, array $valueIds) {
            $result = [];

            foreach ($carry as $combination) {
                foreach ($valueIds as $valueId) {
                    $result[] = [...$combination, $valueId];
                }
            }

            return $result;
        }, [[]]);
    }

    /**
     * @param  list<int>  $valueIds
     */
    private function combinationKey(array $valueIds): string
    {
        $sorted = $valueIds;
        sort($sorted);

        return implode(',', $sorted);
    }

    /**
     * @param  list<int>  $combination
     */
    private function generateSku(Product $product, array $combination): string
    {
        return $product->sku.'-'.implode('-', $combination);
    }
}
