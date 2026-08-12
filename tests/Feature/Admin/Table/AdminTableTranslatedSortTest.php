<?php

use App\Models\Brand;
use App\Models\BrandTranslation;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('mysql-critical');

it('sorts by a translated column via the auto-joined translation table', function () {
    actingAdminWithRole();

    $z = Brand::factory()->create();
    $z->translate('ar')->update(['name' => 'ياسمين']);

    $a = Brand::factory()->create();
    $a->translate('ar')->update(['name' => 'أزهار']);

    $response = $this->get(route('admin.brands.index', ['sort' => 'name', 'direction' => 'asc']), ['Accept' => 'application/json']);

    $response->assertOk();
    $ids = collect($response->json('rows'))->pluck('id')->all();

    expect(array_search($a->id, $ids, true))->toBeLessThan(array_search($z->id, $ids, true));
});

it('still shows a row with no translation in the current locale (LEFT JOIN, not INNER)', function () {
    actingAdminWithRole();

    $brand = Brand::factory()->create();
    // Only an English translation - no Arabic row for the app's default
    // locale at all.
    $brand->translations()->where('locale', 'ar')->delete();
    BrandTranslation::query()->updateOrCreate(
        ['brand_id' => $brand->id, 'locale' => 'en'],
        ['name' => 'English Only Brand']
    );

    $response = $this->get(route('admin.brands.index', ['sort' => 'name', 'direction' => 'asc']), ['Accept' => 'application/json']);

    $response->assertOk();
    $ids = collect($response->json('rows'))->pluck('id')->all();

    // An INNER JOIN would have silently dropped this row entirely.
    expect($ids)->toContain($brand->id);
});

it('does not corrupt a withCount() column when sorting by a translated column', function () {
    actingAdminWithRole();

    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    $response = $this->get(route('admin.brands.index', ['sort' => 'name', 'direction' => 'asc']), ['Accept' => 'application/json']);

    $response->assertOk();
    $row = collect($response->json('rows'))->firstWhere('id', $brand->id);

    expect((int) $row['products_count'])->toBe(1);
});
