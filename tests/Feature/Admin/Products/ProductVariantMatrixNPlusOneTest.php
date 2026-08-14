<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductVariantMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Batch 3.2-B Task 2's own requirement - the variants tab must not N+1 at
 * 50 variants. Same 5-vs-50 comparison pattern as
 * tests/Feature/Admin/Table/ProductsTableNPlusOneTest.php and
 * tests/Feature/Admin/Table/AdminTableNPlusOneTest.php - the query count
 * loading the edit page must stay fixed regardless of variant count, since
 * ProductsController::edit() eager-loads attributeValues.attribute/
 * attributeValues.translations plus withCount('movements') for every
 * variant in one shot rather than per-row.
 */
it('keeps the edit page query count fixed at 50 variants vs 5', function () {
    actingAdminWithRole();
    $category = Category::factory()->create();
    $product = Product::factory()->create(['primary_category_id' => $category->id]);

    $size = Attribute::factory()->variant()->create();
    $values = AttributeValue::factory()->count(5)->create(['attribute_id' => $size->id]);

    app(ProductVariantMatrixService::class)->generateMatrix($product, [$size->id => $values->pluck('id')->all()]);

    // Warm the permission cache first - its bootstrap queries on a cold
    // cache are a one-time cost unrelated to variant count (see
    // ProductsTableNPlusOneTest.php's identical reasoning).
    $this->get(route('admin.products.edit', $product->id))->assertOk();

    DB::enableQueryLog();
    $this->get(route('admin.products.edit', $product->id))->assertOk();
    $queriesForFive = count(DB::getQueryLog());
    DB::disableQueryLog();

    $color = Attribute::factory()->variant()->create();
    $colorValues = AttributeValue::factory()->count(10)->create(['attribute_id' => $color->id]); // 5 sizes x 10 colors = 50
    app(ProductVariantMatrixService::class)->generateMatrix(
        $product,
        [$size->id => $values->pluck('id')->all(), $color->id => $colorValues->pluck('id')->all()],
        [$color->id => $colorValues->first()->id],
    );

    expect($product->variants()->count())->toBe(50);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('admin.products.edit', $product->id))->assertOk();
    $queriesForFifty = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesForFifty)->toBe($queriesForFive);
});
