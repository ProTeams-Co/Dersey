<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createCatalogProducts(int $count, Category $category): void
{
    Product::factory()->count($count)->create()->each(
        fn (Product $product) => $product->categories()->attach($category->id)
    );
}

it('keeps the query count fixed when loading products with translations and categories, regardless of row count', function () {
    $category = Category::factory()->create();

    createCatalogProducts(10, $category);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $products = Product::withCurrentTranslation('ar')->with('categories')->get();
    foreach ($products as $product) {
        $product->translate('ar');
        $product->categories;
    }

    $queriesForTenRows = count(DB::getQueryLog());
    DB::disableQueryLog(); // stop capturing before creating more rows below

    createCatalogProducts(40, $category); // 50 rows total now

    DB::flushQueryLog();
    DB::enableQueryLog();

    $products = Product::withCurrentTranslation('ar')->with('categories')->get();
    foreach ($products as $product) {
        $product->translate('ar');
        $product->categories;
    }

    $queriesForFiftyRows = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Exact equality, not "fewer than the N+1 case" - see
    // HasTranslationsNPlusOneTest for the same reasoning: a "fewer"
    // assertion would still pass for an accidental N+1 that happens to be
    // cheaper than the naive version.
    expect($queriesForTenRows)->toBe($queriesForFiftyRows);
});
