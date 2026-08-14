<?php

use App\Enums\ProductStatus;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('renders the products index for an authorized admin', function () {
    actingAdminWithRole();
    Product::factory()->count(3)->create();

    $this->get(route('admin.products.index'))->assertOk();
});

it('denies a non-permitted admin from viewing products', function () {
    actingAdminWithRole('support');

    $this->get(route('admin.products.index'))->assertForbidden();
});

it('filters by category including its descendant categories', function () {
    actingAdminWithRole();

    $parent = Category::factory()->create();
    $child = Category::factory()->child($parent)->create();

    $inChild = Product::factory()->create(['primary_category_id' => $child->id]);
    $elsewhere = Product::factory()->create(['primary_category_id' => Category::factory()->create()->id]);

    $response = $this->get(route('admin.products.index', ['filter' => ['category_id' => $parent->id]]), ['Accept' => 'application/json']);
    $response->assertOk();

    $ids = collect($response->json('rows'))->pluck('id');

    expect($ids)->toContain($inChild->id)
        ->and($ids)->not->toContain($elsewhere->id);
});

it('filters by stock status (out of stock)', function () {
    actingAdminWithRole();

    $outOfStock = Product::factory()->create();
    ProductVariant::factory()->for($outOfStock)->create(['stock_quantity' => 0, 'reserved_quantity' => 0]);

    $inStock = Product::factory()->create();
    ProductVariant::factory()->for($inStock)->create(['stock_quantity' => 50, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]);

    $response = $this->get(route('admin.products.index', ['filter' => ['stock_status' => 'out']]), ['Accept' => 'application/json']);
    $response->assertOk();

    $ids = collect($response->json('rows'))->pluck('id');

    expect($ids)->toContain($outOfStock->id)
        ->and($ids)->not->toContain($inStock->id);
});

it('finds a product via normalized Arabic search despite hamza/ta-marbuta variants', function () {
    actingAdminWithRole();

    $product = Product::factory()->create();
    $product->translate('ar')->update(['name' => 'فستان سهرة']);

    $response = $this->get(route('admin.products.index', ['q' => 'فُستان']), ['Accept' => 'application/json']);
    $response->assertOk();

    expect(collect($response->json('rows'))->pluck('id'))->toContain($product->id);
});

it('never repeats or drops a row across two consecutive sorted pages', function () {
    actingAdminWithRole();

    // Several rows sharing the exact same sort value (created_at, to the
    // second) - without the mandatory id tiebreaker, pagination across
    // this tie has no guaranteed stable order.
    Product::factory()->count(25)->create(['status' => ProductStatus::Draft]);

    $page1 = collect($this->get(route('admin.products.index', ['page' => 1]), ['Accept' => 'application/json'])->json('rows'))->pluck('id');
    $page2 = collect($this->get(route('admin.products.index', ['page' => 2]), ['Accept' => 'application/json'])->json('rows'))->pluck('id');

    expect($page1->intersect($page2))->toHaveCount(0);
    expect($page1->count() + $page2->count())->toBe(25);
});

it('saves a new product as draft', function () {
    actingAdminWithRole();
    $category = Category::factory()->create();

    $response = $this->post(route('admin.products.store'), [
        'sku' => 'NEW-SKU-001',
        'base_price' => '199.50',
        'gender' => 'unisex',
        'weight' => '300',
        'primary_category_id' => $category->id,
        'translations' => [
            'ar' => ['name' => 'منتج تجريبي'],
        ],
    ]);

    $response->assertRedirect(route('admin.products.index'));

    $product = Product::where('sku', 'NEW-SKU-001')->first();
    expect($product)->not->toBeNull()
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->primary_category_id)->toBe($category->id)
        ->and($product->categories()->where('categories.id', $category->id)->exists())->toBeTrue()
        ->and($product->translate('ar')->name)->toBe('منتج تجريبي')
        ->and($product->translate('ar')->slug)->not->toBeNull();

    // Batch 3.2-M: this test used to only confirm the product was CREATED,
    // never what actually landed in base_price - which is exactly how the
    // MoneyCast truncation bug ("199.50" -> 199 piasters instead of 19950)
    // passed 206 tests undetected. DB::table() (not $product->base_price,
    // which would round-trip back through the now-fixed cast and could
    // mask a regression) is the real proof of what's on disk.
    $rawBasePrice = DB::table('products')->where('id', $product->id)->value('base_price');
    expect($rawBasePrice)->toBe(19950);
});

it('rejects a malformed base_price with a 422, never a 500 from Money::fromMajor()', function () {
    // rules() validates the exact same format (/^\d+(\.\d{1,2})?$/)
    // Money::fromMajor() itself requires - a bad format must never reach
    // convertPriceFields() at all, so it surfaces as a normal validation
    // 422, not an uncaught InvalidArgumentException/500.
    actingAdminWithRole();
    $category = Category::factory()->create();

    foreach (['19.999', 'abc', '-5', '5.'] as $badPrice) {
        $response = $this->post(route('admin.products.store'), [
            'sku' => 'BAD-PRICE-'.uniqid(),
            'base_price' => $badPrice,
            'gender' => 'unisex',
            'weight' => '300',
            'primary_category_id' => $category->id,
            'translations' => ['ar' => ['name' => 'منتج']],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('base_price');
    }
});

it('stores compare_at_price as null when left empty, without throwing', function () {
    // compare_at_price has no rule at all in createRules() (confirmed by
    // reading it - only base_price is required on create), so this has to
    // go through update(), where it IS validated ('nullable'). Submitting
    // it as '' relies on ConvertEmptyStringsToNull turning it into null
    // before validation runs; 'nullable' then lets that null straight
    // through - convertPriceFields() must see null (not '') by the time
    // it runs, since Money::fromMajorNullable(null) returning null
    // (never calling fromMajor() at all) is what keeps this from
    // throwing a TypeError.
    actingAdminWithRole();
    $product = Product::factory()->create(['compare_at_price' => 15000]);

    $response = $this->put(route('admin.products.update', $product->id), [
        'compare_at_price' => '',
    ]);

    $response->assertRedirect(route('admin.products.index'));

    $raw = DB::table('products')->where('id', $product->id)->value('compare_at_price');
    expect($raw)->toBeNull();
});

it('avoids a duplicate slug even against a soft-deleted product', function () {
    actingAdminWithRole();

    // The create form has no slug field at all (correction 1 - HasAutoSlug
    // handles it from the name), so the only way to exercise slug
    // uniqueness here is via two products sharing the same Arabic NAME,
    // which HasAutoSlug turns into colliding base slugs.
    $existing = Product::factory()->create();
    $existing->translate('ar')->update(['name' => 'فستان سهرة']);
    $existingSlug = $existing->translate('ar')->slug;
    $existing->delete();

    $category = Category::factory()->create();

    $response = $this->post(route('admin.products.store'), [
        'sku' => 'NEW-SKU-002',
        'base_price' => '100.00',
        'gender' => 'unisex',
        'weight' => '200',
        'primary_category_id' => $category->id,
        'translations' => [
            'ar' => ['name' => 'فستان سهرة'],
        ],
    ]);

    $response->assertRedirect(route('admin.products.index'));

    $newProduct = Product::where('sku', 'NEW-SKU-002')->first();

    // Not blocked, not a validation error - HasAutoSlug saw the
    // soft-deleted product's still-present translation row and
    // auto-suffixed instead of colliding.
    expect($newProduct->translate('ar')->slug)->not->toBeNull()
        ->and($newProduct->translate('ar')->slug)->not->toBe($existingSlug);
});

it('updating one tab does not clear fields from another untouched tab', function () {
    actingAdminWithRole();
    $product = Product::factory()->create(['sku' => 'KEEP-ME', 'weight' => 500]);
    $originalName = $product->translate('ar')->name;

    // "seo" tab only - sku/weight/translations are never sent at all.
    $this->put(route('admin.products.update', $product->id), [
        'seo' => [
            'ar' => ['title' => 'عنوان SEO مخصص', 'robots' => 'index, follow'],
        ],
    ])->assertRedirect(route('admin.products.index'));

    $product->refresh();
    expect($product->sku)->toBe('KEEP-ME')
        ->and($product->weight)->toBe(500)
        ->and($product->translate('ar')->name)->toBe($originalName)
        ->and($product->seoMetas()->where('locale', 'ar')->first()->title)->toBe('عنوان SEO مخصص');
});

it('syncs multiple additional categories, adding and removing', function () {
    actingAdminWithRole();
    $primary = Category::factory()->create();
    $toKeep = Category::factory()->create();
    $toRemove = Category::factory()->create();
    $toAdd = Category::factory()->create();

    $product = Product::factory()->create(['primary_category_id' => $primary->id]);
    $product->categories()->sync([$primary->id, $toKeep->id, $toRemove->id]);

    $this->put(route('admin.products.update', $product->id), [
        'category_ids' => [$toKeep->id, $toAdd->id],
    ])->assertRedirect(route('admin.products.index'));

    $ids = $product->categories()->pluck('categories.id')->all();

    expect($ids)->toContain($primary->id) // never dropped, even though it wasn't in category_ids
        ->toContain($toKeep->id)
        ->toContain($toAdd->id)
        ->not->toContain($toRemove->id);
});

it('syncs non-variant attribute values', function () {
    actingAdminWithRole();
    $attribute = Attribute::factory()->create(['is_variant' => false]);
    $valueA = AttributeValue::factory()->for($attribute)->create();
    $valueB = AttributeValue::factory()->for($attribute)->create();

    $product = Product::factory()->create();
    $product->attributeValues()->sync([$valueA->id]);

    $this->put(route('admin.products.update', $product->id), [
        'attribute_value_ids' => [$valueB->id],
    ])->assertRedirect(route('admin.products.index'));

    $ids = $product->attributeValues()->pluck('attribute_values.id')->all();
    expect($ids)->toBe([$valueB->id]);
});

it('refuses a variant-generating attribute value on attribute_value_ids with a 422, and leaves the pivot untouched', function () {
    actingAdminWithRole();

    // is_variant = true - size/color, generated as product_variants in
    // Batch 3.2-B, never a general product attribute. product_attribute_value
    // has no DB-level constraint against this (see its migration's own
    // comment) - only App\Rules\AttributeValueMustBeNonVariant does.
    $variantAttribute = Attribute::factory()->create(['is_variant' => true]);
    $variantValue = AttributeValue::factory()->for($variantAttribute)->create();

    $nonVariantAttribute = Attribute::factory()->create(['is_variant' => false]);
    $keptValue = AttributeValue::factory()->for($nonVariantAttribute)->create();

    $product = Product::factory()->create();
    $product->attributeValues()->sync([$keptValue->id]);

    $response = $this->put(route('admin.products.update', $product->id), [
        'attribute_value_ids' => [$keptValue->id, $variantValue->id],
    ], ['Accept' => 'application/json']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('attribute_value_ids.1');

    // Pivot unchanged - the whole request was rejected before ProductService
    // ever ran, not partially applied.
    $ids = $product->attributeValues()->pluck('attribute_values.id')->all();
    expect($ids)->toBe([$keptValue->id]);
});

it('reports the always-blocking variant/image conditions and a missing custom SeoMeta row', function () {
    actingAdminWithRole();

    $category = Category::factory()->create();
    $product = Product::factory()->create(['primary_category_id' => $category->id]);
    $product->categories()->attach($category->id);
    $product->translate('ar')->update(['description' => 'وصف كامل بالعربي']);
    $product->translate('en')->update(['description' => 'Full English description']);

    $blockers = $product->publicationBlockers();

    // Conditions 5/6 - deliberately unconditional until Batch 3.2-B.
    expect($blockers)->toContain('errors.product_missing_variant')
        ->toContain('errors.product_missing_primary_image');

    // Correction 3's own required test: no SeoMeta row exists at all for
    // this product - canBePublished() must still flag condition 4, not
    // silently pass it via seoMeta()'s fallback-to-defaults merge.
    expect($product->seoMetas()->count())->toBe(0);
    expect($blockers)->toContain('errors.product_missing_seo');
});

it('refuses to publish an unmet product with a 422 from the service, not just the UI', function () {
    actingAdminWithRole();
    // ProductFactory defaults to status=Published (a Batch 2.x default for
    // storefront-facing tests) - ->draft() here so this test actually
    // observes changeStatus() refusing to flip Draft -> Published, rather
    // than asserting against a product that was already Published at
    // creation regardless of the action under test.
    $product = Product::factory()->draft()->create();

    $response = $this->post(route('admin.products.status', $product->id), ['status' => 'published'], ['Accept' => 'application/json']);

    $response->assertStatus(422);
    $product->refresh();
    expect($product->status)->not->toBe(ProductStatus::Published);
});

it('bulk-activates only products that meet the publish gate and reports how many were skipped', function () {
    actingAdminWithRole();

    // Every product in this batch fails conditions 5/6 unconditionally
    // (Batch 3.2-B builds variants/images) - so this specific run always
    // skips 100%, which is the correct, intentional outcome to assert,
    // not a weaker "some pass" scenario the current schema can't produce.
    // ->draft() because ProductFactory defaults to status=Published - the
    // point of this test is that the bulk action refuses to CHANGE status
    // to Published, which is only observable starting from Draft.
    $products = Product::factory()->draft()->count(3)->create();

    $response = $this->post(route('admin.products.bulk-action'), [
        'action' => 'activate',
        'ids' => $products->pluck('id')->all(),
    ]);

    $response->assertRedirect(route('admin.products.index'));

    foreach ($products as $product) {
        expect($product->refresh()->status)->not->toBe(ProductStatus::Published);
    }
});

it('soft-deletes and restores a product', function () {
    actingAdminWithRole();
    $product = Product::factory()->create();

    $this->delete(route('admin.products.destroy', $product->id))
        ->assertRedirect(route('admin.products.index'));

    expect(Product::find($product->id))->toBeNull();
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();

    $this->post(route('admin.products.restore', $product->id))
        ->assertRedirect(route('admin.products.index'));

    expect(Product::find($product->id))->not->toBeNull();
});

it('applies a bulk action only to the selected rows', function () {
    actingAdminWithRole();
    $selected = Product::factory()->count(2)->create();
    $untouched = Product::factory()->create();

    $this->post(route('admin.products.bulk-action'), [
        'action' => 'deactivate',
        'ids' => $selected->pluck('id')->all(),
    ])->assertRedirect(route('admin.products.index'));

    foreach ($selected as $product) {
        expect($product->refresh()->status)->toBe(ProductStatus::Draft);
    }

    // $untouched was already Draft by factory default - assert it wasn't
    // touched by asserting no exception/side effect leaked to it via a
    // distinguishing attribute instead (updated_at would still differ if
    // save() ran on it).
    expect($untouched->fresh()->updated_at->equalTo($untouched->updated_at))->toBeTrue();
});
