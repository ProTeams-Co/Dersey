<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps exactly one primary image per product, demoting the previous one', function () {
    $product = Product::factory()->create();

    $first = ProductImage::factory()->primary()->create(['product_id' => $product->id]);
    expect($first->fresh()->is_primary)->toBeTrue();

    $second = ProductImage::factory()->primary()->create(['product_id' => $product->id]);

    expect($second->fresh()->is_primary)->toBeTrue()
        ->and($first->fresh()->is_primary)->toBeFalse();

    expect(ProductImage::where('product_id', $product->id)->where('is_primary', true)->count())->toBe(1);
});

it('does not let a second product\'s primary image affect the first product\'s primary image', function () {
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();

    $imageA = ProductImage::factory()->primary()->create(['product_id' => $productA->id]);
    ProductImage::factory()->primary()->create(['product_id' => $productB->id]);

    expect($imageA->fresh()->is_primary)->toBeTrue();
});
