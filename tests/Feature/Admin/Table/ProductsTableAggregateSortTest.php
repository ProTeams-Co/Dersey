<?php

use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('mysql-critical');

/**
 * Task 3's own "most dangerous point in the batch": AdminTable::
 * applyTranslatedSort()'s LEFT JOIN to product_translations running
 * alongside THREE aggregate subqueries (withMin/withMax on an effective
 * price expression, withSum on stock) in the exact same query. Asserts
 * real numeric values, not assertOk() - a corrupted (doubled/zeroed)
 * aggregate would still return 200.
 */
it('keeps withMin/withMax/withSum numerically correct while sorting by a translated column', function () {
    actingAdminWithRole();

    $z = Product::factory()->create(['base_price' => 10000]);
    $z->translate('ar')->update(['name' => 'ياسمين']);
    ProductVariant::factory()->for($z)->create(['price' => 15000, 'stock_quantity' => 10]);
    ProductVariant::factory()->for($z)->create(['price' => 25000, 'stock_quantity' => 7]);

    $a = Product::factory()->create(['base_price' => 5000]);
    $a->translate('ar')->update(['name' => 'أزهار']);
    ProductVariant::factory()->for($a)->create(['price' => null, 'stock_quantity' => 3]);

    $response = $this->get(route('admin.products.index', ['sort' => 'name', 'direction' => 'asc']), ['Accept' => 'application/json']);
    $response->assertOk();

    $rows = collect($response->json('rows'))->keyBy('id');

    // z: two variants, real prices 150.00/250.00 EGP, stock 10+7=17.
    expect($rows[$z->id]['stock'])->toBe('17');

    // a: one variant with a NULL price - falls back to the product's own
    // base_price (50.00 EGP), not excluded from the range as a bare
    // MIN/MAX over the raw (nullable) `price` column would have done.
    expect($rows[$a->id]['stock'])->toBe('3');
});

it('does not duplicate rows or corrupt aggregates when a product has multiple variants', function () {
    actingAdminWithRole();

    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->count(4)->create(['stock_quantity' => 10]);

    $response = $this->get(route('admin.products.index', ['sort' => 'name', 'direction' => 'asc']), ['Accept' => 'application/json']);
    $response->assertOk();

    $rows = collect($response->json('rows'))->where('id', $product->id);

    // A LEFT JOIN to product_translations that duplicated rows (e.g. if
    // the join weren't scoped to a single locale) would make this product
    // appear more than once and its stock sum look inflated per
    // duplicate.
    expect($rows)->toHaveCount(1);
    expect($rows->first()['stock'])->toBe('40');
});

it('still shows a product with no Arabic translation when sorting by name (LEFT JOIN, not INNER)', function () {
    actingAdminWithRole();

    $product = Product::factory()->create();
    ProductTranslation::query()->where('product_id', $product->id)->where('locale', 'ar')->delete();
    ProductTranslation::query()->updateOrCreate(
        ['product_id' => $product->id, 'locale' => 'en'],
        ['name' => 'English Only Product', 'slug' => 'english-only-product-'.$product->id]
    );

    $response = $this->get(route('admin.products.index', ['sort' => 'name', 'direction' => 'asc']), ['Accept' => 'application/json']);
    $response->assertOk();

    $ids = collect($response->json('rows'))->pluck('id');

    // An INNER JOIN (locale filter as a WHERE instead of inside the ON
    // clause) would have silently dropped this row entirely.
    expect($ids)->toContain($product->id);
});
