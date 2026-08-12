<?php

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the brands index without errors', function () {
    actingAdminWithRole();
    Brand::factory()->count(3)->create();

    $this->get(route('admin.brands.index'))->assertOk();
});

it('creates a brand with translations for both locales', function () {
    actingAdminWithRole();

    $response = $this->post(route('admin.brands.store'), [
        'translations' => [
            'ar' => ['name' => 'براند تجريبي', 'slug' => 'brand-test-ar'],
            'en' => ['name' => 'Test Brand', 'slug' => 'brand-test-en'],
        ],
        'is_active' => '1',
        'is_featured' => '0',
        'sort' => 5,
    ]);

    $response->assertRedirect(route('admin.brands.index'));

    $brand = Brand::first();
    expect($brand)->not->toBeNull()
        ->and($brand->translate('ar')->name)->toBe('براند تجريبي')
        ->and($brand->translate('en')->name)->toBe('Test Brand')
        ->and($brand->is_active)->toBeTrue();
});

it('updates an existing brand', function () {
    actingAdminWithRole();
    $brand = Brand::factory()->create();

    $response = $this->put(route('admin.brands.update', $brand->id), [
        'translations' => [
            'ar' => ['name' => 'اسم محدّث', 'slug' => $brand->translate('ar')->slug],
            'en' => ['name' => 'Updated Name', 'slug' => $brand->translate('en')->slug],
        ],
        'is_active' => '0',
        'is_featured' => '0',
        'sort' => 9,
    ]);

    $response->assertRedirect(route('admin.brands.index'));

    $brand->refresh();
    expect($brand->translate('ar')->name)->toBe('اسم محدّث')
        ->and($brand->is_active)->toBeFalse();
});

it('deletes a brand', function () {
    actingAdminWithRole();
    $brand = Brand::factory()->create();

    $this->delete(route('admin.brands.destroy', $brand->id))
        ->assertRedirect(route('admin.brands.index'));

    expect(Brand::find($brand->id))->toBeNull();
});

it('runs a bulk deactivate action', function () {
    actingAdminWithRole();
    $brands = Brand::factory()->count(3)->create(['is_active' => true]);

    $this->post(route('admin.brands.bulk-action'), [
        'action' => 'deactivate',
        'ids' => $brands->pluck('id')->all(),
    ])->assertRedirect(route('admin.brands.index'));

    expect(Brand::where('is_active', false)->count())->toBe(3);
});

it('denies a non-permitted admin from viewing brands', function () {
    actingAdminWithRole('support');

    $this->get(route('admin.brands.index'))->assertForbidden();
});
