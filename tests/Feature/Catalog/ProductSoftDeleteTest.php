<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('removes a soft-deleted product from its category results', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    expect($category->products()->count())->toBe(1);

    $product->delete();

    expect($category->products()->count())->toBe(0)
        ->and(Product::withTrashed()->find($product->id))->not->toBeNull();
});
