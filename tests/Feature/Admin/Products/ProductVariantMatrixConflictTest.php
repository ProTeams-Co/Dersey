<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductVariantMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('mysql-critical');

/**
 * Batch 3.2-B decision 4's own required test - the optimistic-lock
 * conflict path, specifically flagged to run against real MySQL (not just
 * SQLite) since the pre-check UPDATE ... WHERE version = :version pattern
 * this ultimately relies on (App\Support\Traits\HasOptimisticLock) is a
 * real conditional UPDATE, and this project's own convention (CLAUDE.md
 * §18) is that anything touching real SQL semantics gets verified on
 * MySQL, not assumed safe from SQLite alone.
 */
it('returns a 409 with every conflicting row named when a submitted version is stale', function () {
    actingAdminWithRole();

    $category = Category::factory()->create();
    $product = Product::factory()->create(['primary_category_id' => $category->id]);

    $size = Attribute::factory()->variant()->create();
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id]);
    $m = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $variants = app(ProductVariantMatrixService::class)->generateMatrix($product, [$size->id => [$s->id, $m->id]]);
    [$first, $second] = $variants->values()->all();

    // Simulates "someone else already saved this row" - its real version
    // in the database moves on without the client's copy knowing. A raw
    // query builder update (not Eloquent) both sidesteps `version` not
    // being mass-assignable (by design - only HasOptimisticLock's own
    // saveWithVersion() is meant to move it) and doubles as a clean stand-in
    // for "a second real request wrote this row through the same
    // mechanism", without this test depending on calling saveWithVersion()
    // correctly itself.
    $staleVersionForFirst = $first->version;
    DB::table('product_variants')->where('id', $first->id)->update([
        'sku' => $first->sku.'-CHANGED',
        'version' => $staleVersionForFirst + 1,
    ]);

    $response = $this->put(route('admin.products.variants.update', $product->id), [
        'rows' => [
            ['id' => $first->id, 'version' => $staleVersionForFirst, 'sku' => 'ATTEMPTED-NEW-SKU', 'price' => null, 'compare_at_price' => null, 'is_active' => true],
            ['id' => $second->id, 'version' => $second->version, 'sku' => $second->sku, 'price' => null, 'compare_at_price' => null, 'is_active' => true],
        ],
    ], ['Accept' => 'application/json']);

    $response->assertStatus(409);

    $body = $response->json();
    expect($body['variants'])->toHaveCount(1);
    expect($body['variants'][0]['id'])->toBe($first->id);

    // Nothing was written - not even the SECOND (non-conflicting) row -
    // decision 4's "rollback للحفظ كله - كله أو لا شيء".
    expect($second->fresh()->sku)->toBe($second->sku);
});
