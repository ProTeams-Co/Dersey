<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Task 3's own required check: the query count for the products list must
 * stay fixed as row count grows, both under the default sort and under the
 * highest-risk combination (translated-name sort, which adds the
 * applyTranslatedSort() JOIN on top of the withMin/withMax/withSum
 * aggregates already on every request).
 */
it('keeps the query count fixed at 50 products vs 5 under the default sort', function () {
    actingAdminWithRole();
    $category = Category::factory()->create();

    Product::factory()->count(5)->create(['primary_category_id' => $category->id]);

    // Warms spatie/laravel-permission's role/permission cache first - its
    // 4 bootstrap queries on a cold cache are a one-time cost unrelated to
    // product row count, and would otherwise look like a false N+1 signal
    // purely because this test's first HTTP call happens to be the 5-row
    // one.
    $this->get(route('admin.products.index'), ['Accept' => 'application/json'])->assertOk();

    DB::enableQueryLog();
    $this->get(route('admin.products.index'), ['Accept' => 'application/json'])->assertOk();
    $queriesForFive = count(DB::getQueryLog());
    DB::disableQueryLog();

    Product::factory()->count(45)->create(['primary_category_id' => $category->id]); // 50 total
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('admin.products.index'), ['Accept' => 'application/json'])->assertOk();
    $queriesForFifty = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesForFifty)->toBe($queriesForFive);
});

it('keeps the query count fixed at 50 products vs 5 when sorted by translated name', function () {
    actingAdminWithRole();
    $category = Category::factory()->create();

    Product::factory()->count(5)->create(['primary_category_id' => $category->id]);

    // Same permission-cache warm-up as the default-sort test above.
    $this->get(route('admin.products.index', ['sort' => 'name', 'direction' => 'asc']), ['Accept' => 'application/json'])->assertOk();

    DB::enableQueryLog();
    $this->get(route('admin.products.index', ['sort' => 'name', 'direction' => 'asc']), ['Accept' => 'application/json'])->assertOk();
    $queriesForFive = count(DB::getQueryLog());
    DB::disableQueryLog();

    Product::factory()->count(45)->create(['primary_category_id' => $category->id]); // 50 total
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('admin.products.index', ['sort' => 'name', 'direction' => 'asc']), ['Accept' => 'application/json'])->assertOk();
    $queriesForFifty = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesForFifty)->toBe($queriesForFive);
});
