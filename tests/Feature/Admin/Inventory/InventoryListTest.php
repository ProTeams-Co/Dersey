<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeInventoryProduct(): Product
{
    $category = Category::factory()->create();

    return Product::factory()->create(['primary_category_id' => $category->id]);
}

it('renders the inventory list for an authorized admin, and denies an unauthorized one', function () {
    actingAdminWithRole();
    $this->get(route('admin.inventory.index'))->assertOk();
});

it('denies a non-permitted admin from viewing the inventory list', function () {
    actingAdminWithRole('support');
    $this->get(route('admin.inventory.index'))->assertForbidden();
});

it('filters to only out-of-stock variants (available_quantity <= 0), a computed value', function () {
    actingAdminWithRole();
    $product = makeInventoryProduct();

    $out = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 5, 'reserved_quantity' => 5]);
    $in = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 20, 'reserved_quantity' => 0]);

    $response = $this->get(route('admin.inventory.index', ['filter' => ['stock_status' => 'out']]), ['Accept' => 'application/json']);
    $ids = collect($response->json('rows'))->pluck('id');

    expect($ids)->toContain($out->id)->not->toContain($in->id);
});

it('filters to low-stock variants, respecting EACH variant\'s own low_stock_threshold', function () {
    actingAdminWithRole();
    $product = makeInventoryProduct();

    // available = 3 in both cases, but only the first is "low" relative
    // to ITS OWN threshold - proves the filter reads low_stock_threshold
    // per row, not a single global constant.
    $lowForItsThreshold = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 3, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]);
    $notLowForItsThreshold = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 3, 'reserved_quantity' => 0, 'low_stock_threshold' => 1]);

    $response = $this->get(route('admin.inventory.index', ['filter' => ['stock_status' => 'low']]), ['Accept' => 'application/json']);
    $ids = collect($response->json('rows'))->pluck('id');

    expect($ids)->toContain($lowForItsThreshold->id)->not->toContain($notLowForItsThreshold->id);
});

it('filters by category, including descendant categories', function () {
    actingAdminWithRole();
    $parent = Category::factory()->create();
    $child = Category::factory()->child($parent)->create();

    $childProduct = Product::factory()->create(['primary_category_id' => $child->id]);
    $childVariant = ProductVariant::factory()->create(['product_id' => $childProduct->id]);

    $unrelatedProduct = makeInventoryProduct();
    $unrelatedVariant = ProductVariant::factory()->create(['product_id' => $unrelatedProduct->id]);

    $response = $this->get(route('admin.inventory.index', ['filter' => ['category_id' => $parent->id]]), ['Accept' => 'application/json']);
    $ids = collect($response->json('rows'))->pluck('id');

    expect($ids)->toContain($childVariant->id)->not->toContain($unrelatedVariant->id);
});

it('finds a variant via normalized Arabic search on the product name despite hamza/ta-marbuta variants', function () {
    actingAdminWithRole();
    $product = makeInventoryProduct();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $product->translate('ar')->update(['name' => 'قهوة أمريكانو']);

    $response = $this->get(route('admin.inventory.index', ['q' => 'قهوه امريكانو']), ['Accept' => 'application/json']);
    $ids = collect($response->json('rows'))->pluck('id');

    expect($ids)->toContain($variant->id);
});

it('never repeats or drops a row across two consecutive sorted pages', function () {
    actingAdminWithRole();
    $product = makeInventoryProduct();

    // Several rows sharing the exact same stock_quantity - without the
    // mandatory id tiebreaker, pagination across this tie has no
    // guaranteed stable order. More than InventoryTable::perPage() (50)
    // so page 2 is actually non-empty - 25 rows against a 50-per-page
    // table would put everything on page 1 and prove nothing.
    ProductVariant::factory()->count(75)->create(['product_id' => $product->id, 'stock_quantity' => 10]);

    $page1 = collect($this->get(route('admin.inventory.index', ['page' => 1]), ['Accept' => 'application/json'])->json('rows'))->pluck('id');
    $page2 = collect($this->get(route('admin.inventory.index', ['page' => 2]), ['Accept' => 'application/json'])->json('rows'))->pluck('id');

    expect($page1->intersect($page2))->toHaveCount(0);
    expect($page1->count() + $page2->count())->toBe(75);
});

/**
 * Batch 3.3-fix Task 1 - built to check a claimed bug (tiebreaker
 * matching the primary sort's own direction, instead of a fixed asc,
 * could repeat/drop rows across descending pages). Run against the
 * unmodified code on both SQLite and real MySQL before any change was
 * made: zero overlap, zero drops, on both. `ORDER BY stock_quantity
 * DESC, product_variants.id DESC` is just as stable a total order as
 * ...id ASC would be, as long as both page requests use the identical
 * ORDER BY (they do - direction comes from the same query string on
 * both). Kept here as permanent regression coverage for the descending
 * + tied-values case the original test above didn't exercise, not
 * because a fix was needed - user confirmed after seeing this pass on
 * both drivers: leave the code as-is.
 */
it('never repeats or drops a row across two consecutive DESCENDING pages with tied values', function () {
    actingAdminWithRole();
    $product = makeInventoryProduct();

    ProductVariant::factory()->count(75)->create(['product_id' => $product->id, 'stock_quantity' => 10]);

    $page1 = collect($this->get(route('admin.inventory.index', ['sort' => 'stock_quantity', 'direction' => 'desc', 'page' => 1]), ['Accept' => 'application/json'])->json('rows'))->pluck('id');
    $page2 = collect($this->get(route('admin.inventory.index', ['sort' => 'stock_quantity', 'direction' => 'desc', 'page' => 2]), ['Accept' => 'application/json'])->json('rows'))->pluck('id');

    expect($page1->intersect($page2))->toHaveCount(0);
    expect($page1->count() + $page2->count())->toBe(75);
});

it('loads the inventory list with 50 variants in a fixed query count - no N+1', function () {
    actingAdminWithRole();
    $color = Attribute::factory()->color()->create();
    $red = AttributeValue::factory()->create(['attribute_id' => $color->id]);

    // One variant per product (not 50 variants of the same product) -
    // realistic (an inventory list spans many products) and sidesteps
    // ProductVariant::syncOptionValues()'s own same-attribute-set-as-
    // siblings check entirely, since a lone variant trivially matches
    // itself.
    for ($i = 0; $i < 5; $i++) {
        $variant = ProductVariant::factory()->create(['product_id' => makeInventoryProduct()->id]);
        $variant->syncOptionValues([$red->id]);
    }

    $this->get(route('admin.inventory.index'))->assertOk();
    DB::enableQueryLog();
    $this->get(route('admin.inventory.index'))->assertOk();
    $queriesForFive = count(DB::getQueryLog());
    DB::disableQueryLog();

    for ($i = 0; $i < 45; $i++) {
        $variant = ProductVariant::factory()->create(['product_id' => makeInventoryProduct()->id]);
        $variant->syncOptionValues([$red->id]);
    }

    expect(ProductVariant::count())->toBe(50);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $response = $this->get(route('admin.inventory.index'));
    $response->assertOk();
    $queriesForFifty = count(DB::getQueryLog());
    DB::disableQueryLog();

    // InventoryTable::perPage() = 50, so this second request genuinely
    // renders all 50 rows on one page, not just 20 of them.
    expect($queriesForFifty)->toBe($queriesForFive);
});
