<?php

use App\Enums\InventoryMovementType;
use App\Exceptions\VariantDeletionBlockedException;
use App\Exceptions\VariantMatrixInconsistentException;
use App\Exceptions\VariantMatrixLimitExceededException;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Services\Catalog\ProductVariantMatrixService;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeVariantProduct(): Product
{
    $category = Category::factory()->create();

    return Product::factory()->create(['primary_category_id' => $category->id]);
}

/**
 * AttributeValueFactory::configure()'s afterCreating() hook generates
 * translations via fake('ar_EG')->unique()->word() / fake()->unique()->word() -
 * both locales' word() providers only have ~155-160 distinct entries total
 * (confirmed empirically), so no amount of resetting Faker's unique
 * tracking can produce 200+ genuinely distinct values from them. Bulk
 * inserts directly instead (deterministic, non-unique-constrained
 * translation text - attribute_value_translations only enforces
 * unique(attribute_value_id, locale), never uniqueness of `value` itself),
 * bypassing the factory entirely for tests that only need real ids for
 * combination math, not realistic-looking labels.
 *
 * @return list<int>
 */
function bulkCreateAttributeValues(int $attributeId, int $count): array
{
    $now = now();
    $rows = array_fill(0, $count, ['attribute_id' => $attributeId, 'sort' => 0, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('attribute_values')->insert($rows);

    $ids = DB::table('attribute_values')->where('attribute_id', $attributeId)->orderBy('id')->pluck('id')->all();

    $translationRows = [];
    foreach ($ids as $index => $id) {
        $translationRows[] = ['attribute_value_id' => $id, 'locale' => 'ar', 'value' => "قيمة {$index}", 'created_at' => $now, 'updated_at' => $now];
        $translationRows[] = ['attribute_value_id' => $id, 'locale' => 'en', 'value' => "Value {$index}", 'created_at' => $now, 'updated_at' => $now];
    }
    DB::table('attribute_value_translations')->insert($translationRows);

    return $ids;
}

it('generates the full cartesian count from scratch - 2 sizes x 3 colors = 6', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $m = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $color = Attribute::factory()->variant()->create();
    $red = AttributeValue::factory()->create(['attribute_id' => $color->id]);
    $blue = AttributeValue::factory()->create(['attribute_id' => $color->id]);
    $green = AttributeValue::factory()->create(['attribute_id' => $color->id]);

    $variants = app(ProductVariantMatrixService::class)->generateMatrix($product, [
        $size->id => [$s->id, $m->id],
        $color->id => [$red->id, $blue->id, $green->id],
    ]);

    expect($variants)->toHaveCount(6);
    expect($product->variants()->count())->toBe(6);
});

it('preserves existing variant ids when a new attribute axis is added', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $m = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $initial = $service->generateMatrix($product, [$size->id => [$s->id, $m->id]]);
    $originalIds = $initial->pluck('id')->sort()->values()->all();

    $color = Attribute::factory()->variant()->create();
    $red = AttributeValue::factory()->create(['attribute_id' => $color->id]);
    $blue = AttributeValue::factory()->create(['attribute_id' => $color->id]);

    $service->generateMatrix(
        $product,
        [$size->id => [$s->id, $m->id], $color->id => [$red->id, $blue->id]],
        [$color->id => $red->id],
    );

    $survivingIds = ProductVariant::whereIn('id', $originalIds)->pluck('id')->sort()->values()->all();
    expect($survivingIds)->toBe($originalIds);
});

it('applies the chosen default value to every existing variant when adding an attribute', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $m = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $initial = $service->generateMatrix($product, [$size->id => [$s->id, $m->id]]);
    $originalIds = $initial->pluck('id')->all();

    $color = Attribute::factory()->variant()->create();
    $red = AttributeValue::factory()->create(['attribute_id' => $color->id]);
    $blue = AttributeValue::factory()->create(['attribute_id' => $color->id]);

    $service->generateMatrix(
        $product,
        [$size->id => [$s->id, $m->id], $color->id => [$red->id, $blue->id]],
        [$color->id => $red->id],
    );

    foreach ($originalIds as $id) {
        $variant = ProductVariant::with('attributeValues')->find($id);
        expect($variant->attributeValues->pluck('id'))->toContain($red->id);
    }
});

it('generates only the missing combinations when a new value is added to an existing attribute', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $m = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $initial = $service->generateMatrix($product, [$size->id => [$s->id, $m->id]]);
    $originalIds = $initial->pluck('id')->sort()->values()->all();

    $l = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $result = $service->generateMatrix($product, [$size->id => [$s->id, $m->id, $l->id]]);

    expect($result)->toHaveCount(3);
    $resultIds = $result->pluck('id')->sort()->values()->all();
    // both original ids are still present among the 3 - only ONE new row
    // was created for the missing "L" combination.
    expect(array_intersect($originalIds, $resultIds))->toHaveCount(2);
});

it('blocks removing an attribute when an affected variant has physical stock, with a 422', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $variants = $service->generateMatrix($product, [$size->id => [$s->id]]);
    $variant = $variants->first();
    $variant->update(['stock_quantity' => 10]);

    try {
        $service->generateMatrix($product, []);
        expect(false)->toBeTrue('Expected VariantDeletionBlockedException to be thrown.');
    } catch (VariantDeletionBlockedException $e) {
        expect($e->blockedVariants)->toHaveCount(1);
        expect($e->blockedVariants->first()['reasons'])->toContain('errors.variant_protected_stock');
    }

    // Nothing was removed - the variant survives.
    expect(ProductVariant::find($variant->id))->not->toBeNull();
});

it('previews a blocked removal without crashing, and reports the protected variant by label', function () {
    // Regression test: previewMatrix() (and generateMatrix()) originally
    // eager-loaded attributeValues.attribute but not
    // attributeValues.translations - optionsLabel() (called on every
    // blocked variant to build its label) needs both, so this threw a
    // real LazyLoadingViolationException the moment a protected variant
    // was ever actually reported. Never caught by the other mandatory
    // tests because they exercise generateMatrix()'s own exception path
    // directly - caught only via a live admin session's error log.
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $variant = $service->generateMatrix($product, [$size->id => [$s->id]])->first();
    $variant->update(['stock_quantity' => 5]);

    $preview = $service->previewMatrix($product, []);

    expect($preview['removed_protected'])->toHaveCount(1);
    expect($preview['removed_protected'][0]['label'])->not->toBeEmpty();
    expect($preview['removed_protected'][0]['reasons'])->toContain('errors.variant_protected_stock');
});

it('blocks removing an attribute when an affected variant has sales, with a 422', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $variants = $service->generateMatrix($product, [$size->id => [$s->id]]);
    $variant = $variants->first();

    OrderItem::factory()->create(['variant_id' => $variant->id, 'product_id' => $product->id]);

    try {
        $service->generateMatrix($product, []);
        expect(false)->toBeTrue('Expected VariantDeletionBlockedException to be thrown.');
    } catch (VariantDeletionBlockedException $e) {
        expect($e->blockedVariants->first()['reasons'])->toContain('errors.variant_protected_sales');
    }

    expect(ProductVariant::find($variant->id))->not->toBeNull();
});

it('keeps every variant on the same attribute set after add/remove operations', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $m = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $color = Attribute::factory()->variant()->create();
    $red = AttributeValue::factory()->create(['attribute_id' => $color->id]);

    $service = app(ProductVariantMatrixService::class);
    $service->generateMatrix($product, [$size->id => [$s->id, $m->id]]);
    $service->generateMatrix(
        $product,
        [$size->id => [$s->id, $m->id], $color->id => [$red->id]],
        [$color->id => $red->id],
    );

    $variants = $product->variants()->with('attributeValues')->get();
    $attributeSets = $variants->map(
        fn (ProductVariant $v) => $v->attributeValues->pluck('attribute_id')->sort()->values()->implode(',')
    )->unique();

    expect($attributeSets)->toHaveCount(1);
});

it('rolls back the whole bulk save when one row fails mid-transaction', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $m = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $variants = $service->generateMatrix($product, [$size->id => [$s->id, $m->id]]);
    [$first, $second] = $variants->values()->all();

    $otherProduct = makeVariantProduct();
    $collidingSku = 'COLLIDE-'.uniqid();
    ProductVariant::factory()->for($otherProduct)->create(['sku' => $collidingSku]);

    $originalFirstSku = $first->sku;

    $exceptionWasThrown = false;

    try {
        $service->updateVariants($product, [
            ['id' => $first->id, 'version' => $first->version, 'sku' => 'NEW-SKU-'.uniqid(), 'price' => null, 'compare_at_price' => null, 'is_active' => true],
            // Second row's sku collides with a variant on a DIFFERENT
            // product - the DB's own unique(sku) constraint rejects this
            // UPDATE mid-loop, after the first row's UPDATE already ran
            // in the same (uncommitted) transaction.
            ['id' => $second->id, 'version' => $second->version, 'sku' => $collidingSku, 'price' => null, 'compare_at_price' => null, 'is_active' => true],
        ]);
    } catch (Throwable) {
        $exceptionWasThrown = true;
    }

    expect($exceptionWasThrown)->toBeTrue('Expected a database exception.');
    expect($first->fresh()->sku)->toBe($originalFirstSku);
});

it('rejects a duplicate SKU in the bulk save with a 422', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $m = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $variants = $service->generateMatrix($product, [$size->id => [$s->id, $m->id]]);
    [$first, $second] = $variants->values()->all();

    $response = $this->put(route('admin.products.variants.update', $product->id), [
        'rows' => [
            ['id' => $first->id, 'version' => $first->version, 'sku' => $first->sku, 'price' => null, 'compare_at_price' => null, 'is_active' => true],
            ['id' => $second->id, 'version' => $second->version, 'sku' => $first->sku, 'price' => null, 'compare_at_price' => null, 'is_active' => true],
        ],
    ], ['Accept' => 'application/json']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('rows.1.sku');
});

it('records an inventory movement when initial stock is set on a never-touched variant', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $variant = $service->generateMatrix($product, [$size->id => [$s->id]])->first();

    expect($variant->stock_quantity)->toBe(0);
    expect(InventoryMovement::where('variant_id', $variant->id)->count())->toBe(0);

    $service->updateVariants($product, [[
        'id' => $variant->id,
        'version' => $variant->version,
        'sku' => $variant->sku,
        'price' => null,
        'compare_at_price' => null,
        'is_active' => true,
        'initial_stock' => 15,
    ]]);

    $variant->refresh();
    expect($variant->stock_quantity)->toBe(15);

    $movement = InventoryMovement::where('variant_id', $variant->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->type->value)->toBe('in')
        ->and($movement->quantity)->toBe(15);
});

it('never changes stock_quantity for an existing variant that already has movement history', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $variant = $service->generateMatrix($product, [$size->id => [$s->id]])->first();

    // Give it real movement history first (simulates it already went
    // through its own "initial stock" once).
    app(InventoryService::class)->adjust(
        $variant, 20, InventoryMovementType::In
    );
    $variant->refresh();
    expect($variant->stock_quantity)->toBe(20);

    // A later save submits "initial_stock" again (e.g. a stale form field
    // or a user re-typing a number) - this screen must never write
    // stock_quantity directly, and the movement-history guard must refuse
    // to silently re-apply it either.
    $service->updateVariants($product, [[
        'id' => $variant->id,
        'version' => $variant->fresh()->version,
        'sku' => $variant->sku,
        'price' => null,
        'compare_at_price' => null,
        'is_active' => true,
        'initial_stock' => 999,
    ]]);

    expect($variant->fresh()->stock_quantity)->toBe(20);
});

it('lets the publish gate pass condition 5 with at least one active variant', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();
    $product->translate('ar')->update(['description' => 'وصف كامل']);
    $product->translate('en')->update(['description' => 'Full description']);
    $product->seoMetas()->create(['locale' => 'ar', 'title' => 'عنوان', 'description' => 'وصف']);
    $product->seoMetas()->create(['locale' => 'en', 'title' => 'Title', 'description' => 'Description']);

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    app(ProductVariantMatrixService::class)->generateMatrix($product, [$size->id => [$s->id]]);

    expect($product->publicationBlockers())->not->toContain('errors.product_missing_variant');
});

it('blocks condition 5 when every variant is inactive', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $variant = app(ProductVariantMatrixService::class)->generateMatrix($product, [$size->id => [$s->id]])->first();
    $variant->update(['is_active' => false]);

    expect($product->publicationBlockers())->toContain('errors.product_missing_variant');
});

it('blocks condition 6 (primary image) when the product has no primary image', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    expect($product->publicationBlockers())->toContain('errors.product_missing_primary_image');
});

it('lets condition 6 pass once the product has a primary image', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    \App\Models\ProductImage::factory()->for($product)->primary()->create();

    expect($product->publicationBlockers())->not->toContain('errors.product_missing_primary_image');
});

it('is actually publishable (canBePublished() = true, empty blockers) once every condition is genuinely met', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();
    $product->translate('ar')->update(['description' => 'وصف كامل']);
    $product->translate('en')->update(['description' => 'Full description']);
    $product->seoMetas()->create(['locale' => 'ar', 'title' => 'عنوان', 'description' => 'وصف']);
    $product->seoMetas()->create(['locale' => 'en', 'title' => 'Title', 'description' => 'Description']);

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    app(ProductVariantMatrixService::class)->generateMatrix($product, [$size->id => [$s->id]]);

    \App\Models\ProductImage::factory()->for($product)->primary()->create();

    expect($product->publicationBlockers())->toBe([]);
    expect($product->canBePublished())->toBeTrue();
});

it('stores the variant price in piasters via MoneyCast', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $variant = app(ProductVariantMatrixService::class)->generateMatrix($product, [$size->id => [$s->id]])->first();

    app(ProductVariantMatrixService::class)->updateVariants($product, [[
        'id' => $variant->id,
        'version' => $variant->version,
        'sku' => $variant->sku,
        'price' => '199.50',
        'compare_at_price' => null,
        'is_active' => true,
    ]]);

    $raw = DB::table('product_variants')->where('id', $variant->id)->value('price');
    expect($raw)->toBe(19950);
    expect($variant->fresh()->price->minor())->toBe(19950);
});

it('rolls back the whole transaction when surviving variants end up with inconsistent attribute sets', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s1 = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $s2 = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $variantA = $product->variants()->create(['sku' => 'A', 'stock_quantity' => 0, 'low_stock_threshold' => 5]);
    $variantA->version = 0;
    $variantA->syncOptionValues([$s1->id]);

    $variantB = $product->variants()->create(['sku' => 'B', 'stock_quantity' => 0, 'low_stock_threshold' => 5]);
    $variantB->version = 0;
    $variantB->syncOptionValues([$s2->id]);

    $color = Attribute::factory()->variant()->create();
    $c1 = AttributeValue::factory()->create(['attribute_id' => $color->id]);

    // Synthetic corruption: only variant B gets the new attribute's value,
    // bypassing generateMatrix()'s own migration step entirely (which
    // would apply it to every survivor) - simulating exactly the kind of
    // partial-write inconsistency the post-check exists to catch, had
    // some future change broken that guarantee.
    ProductVariantValue::query()->create([
        'variant_id' => $variantB->id,
        'attribute_value_id' => $c1->id,
    ]);

    $service = app(ProductVariantMatrixService::class);
    $method = new ReflectionMethod($service, 'assertConsistentAttributeSets');
    $method->setAccessible(true);

    $threw = false;

    try {
        DB::transaction(function () use ($method, $service, $product, $variantA) {
            // A real write inside the same transaction, to prove the
            // whole transaction rolls back - not just the check itself.
            $variantA->update(['sku' => 'SHOULD-NOT-PERSIST']);
            $method->invoke($service, $product->fresh());
        });
    } catch (VariantMatrixInconsistentException $e) {
        $threw = true;
        expect($e->variants->pluck('id')->all())->toEqualCanonicalizing([$variantA->id, $variantB->id]);
    }

    expect($threw)->toBeTrue('Expected VariantMatrixInconsistentException to be thrown.');
    expect($variantA->fresh()->sku)->toBe('A');
});

it('rejects generateMatrix() with more than 200 combinations, with a 422-style message naming both numbers', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $valueIds = bulkCreateAttributeValues($size->id, 201);

    try {
        app(ProductVariantMatrixService::class)->generateMatrix($product, [$size->id => $valueIds]);
        expect(false)->toBeTrue('Expected VariantMatrixLimitExceededException to be thrown.');
    } catch (VariantMatrixLimitExceededException $e) {
        expect($e->requested)->toBe(201);
        expect($e->limit)->toBe(ProductVariantMatrixService::MAX_COMBINATIONS);
    }
});

it('rejects previewMatrix() with more than 200 combinations too', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $valueIds = bulkCreateAttributeValues($size->id, 201);

    expect(fn () => app(ProductVariantMatrixService::class)->previewMatrix($product, [$size->id => $valueIds]))
        ->toThrow(VariantMatrixLimitExceededException::class);
});

it('writes nothing at all when the combination limit is rejected', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $valueIds = bulkCreateAttributeValues($size->id, 201);

    try {
        app(ProductVariantMatrixService::class)->generateMatrix($product, [$size->id => $valueIds]);
    } catch (VariantMatrixLimitExceededException) {
        // expected
    }

    expect($product->variants()->count())->toBe(0);
});

it('allows exactly 200 combinations - the boundary case', function () {
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $valueIds = bulkCreateAttributeValues($size->id, 200);

    $variants = app(ProductVariantMatrixService::class)->generateMatrix($product, [$size->id => $valueIds]);

    expect($variants)->toHaveCount(200);
});

it('rolls back the whole migration step when it is interrupted partway through', function () {
    // Batch 3.2-B-fix Task 3's own required test: step 2 (applying a new
    // attribute's default value to every surviving variant) no longer
    // calls syncOptionValues() per variant - it calls
    // variantValues()->create() directly, which still goes through
    // ProductVariantValueObserver's per-row checks but NOT
    // syncOptionValues()'s own same-attribute-set-as-siblings check
    // (that check is now absent from this step entirely - see the batch
    // report). The only thing that still guarantees consistency if a
    // mid-loop failure happens is the surrounding DB::transaction()'s
    // rollback, not a validation check - this test proves the rollback
    // actually holds, not just assumes it.
    actingAdminWithRole();
    $product = makeVariantProduct();

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $m = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $l = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $service = app(ProductVariantMatrixService::class);
    $variants = $service->generateMatrix($product, [$size->id => [$s->id, $m->id, $l->id]])->sortBy('id')->values();

    $color = Attribute::factory()->variant()->create();
    $red = AttributeValue::factory()->create(['attribute_id' => $color->id]);

    // Corrupts the THIRD variant ("L") only - directly attaches the EXACT
    // same value ($red) step 2 will later try to add to it, bypassing
    // syncOptionValues() (raw pivot insert). Attaching a DIFFERENT color
    // value here instead would make variantsToRemove() (step 1) correctly
    // flag "L" for replacement instead (its color value wouldn't match
    // the desired $red), which is valid reconciliation behavior, not a
    // failure - the point of this test needs step 2 itself to fail, which
    // only happens when the row it tries to insert already exists
    // verbatim: product_variant_values' own unique(variant_id,
    // attribute_value_id) constraint rejects the exact duplicate outright.
    $thirdVariant = $variants->last();
    ProductVariantValue::query()->create([
        'variant_id' => $thirdVariant->id,
        'attribute_value_id' => $red->id,
    ]);

    $exceptionWasThrown = false;

    try {
        $service->generateMatrix(
            $product,
            [$size->id => [$s->id, $m->id, $l->id], $color->id => [$red->id]],
            [$color->id => $red->id],
        );
    } catch (Throwable) {
        $exceptionWasThrown = true;
    }

    expect($exceptionWasThrown)->toBeTrue('Expected an exception from the third variant.');

    // Rollback proof: "S" and "M" are the two variants step 2 actually
    // touched mid-loop (added $red to each) before "L" hit the unique-
    // constraint violation and the whole transaction rolled back - both
    // must revert to Size-only, exactly as if step 2 never ran.
    //
    // "L" itself is deliberately NOT asserted to be Size-only here: its
    // (variant_id, attribute_value_id) row was inserted as this test's
    // OWN setup, before generateMatrix() (and its transaction) even
    // started - that row was never part of the failed transaction, so it
    // correctly survives untouched. Asserting it away would be checking
    // this test's own fixture, not the service's rollback behavior.
    [$sVariant, $mVariant] = $variants->take(2)->all();

    expect($sVariant->fresh(['attributeValues'])->attributeValues->pluck('attribute_id')->all())->toBe([$size->id]);
    expect($mVariant->fresh(['attributeValues'])->attributeValues->pluck('attribute_id')->all())->toBe([$size->id]);
});
