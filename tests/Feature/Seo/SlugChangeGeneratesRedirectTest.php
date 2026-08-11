<?php

use App\Enums\RedirectStatusCode;
use App\Models\Category;
use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('auto-creates a redirect from the old path to the new one when a category slug changes', function () {
    $category = Category::factory()->create();
    $translation = $category->translate('ar');
    $oldSlug = $translation->slug;

    $translation->update(['slug' => 'new-category-slug']);

    $redirect = Redirect::query()->where('from_path', "/ar/categories/{$oldSlug}")->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->to_path)->toBe('/ar/categories/new-category-slug')
        ->and($redirect->status_code)->toBe(RedirectStatusCode::Permanent)
        ->and($redirect->is_active)->toBeTrue();
});

it('auto-creates a redirect from the old path to the new one when a product slug changes', function () {
    $product = Product::factory()->create();
    $translation = $product->translate('ar');
    $oldSlug = $translation->slug;

    $translation->update(['slug' => 'new-product-slug']);

    $redirect = Redirect::query()->where('from_path', "/ar/products/{$oldSlug}")->first();

    expect($redirect)->not->toBeNull()
        ->and($redirect->to_path)->toBe('/ar/products/new-product-slug');
});

it('does not create a redirect when the slug does not actually change', function () {
    $category = Category::factory()->create();
    $translation = $category->translate('ar');

    $translation->update(['name' => 'اسم مختلف تمامًا']);

    expect(Redirect::query()->count())->toBe(0);
});
