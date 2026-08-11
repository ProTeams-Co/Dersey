<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('counts products in a category and every one of its descendant branches, once each', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->child($root)->create();
    $grandchild = Category::factory()->child($child)->create();
    $unrelated = Category::factory()->create();

    Product::factory()->create()->categories()->attach($root->id);
    Product::factory()->create()->categories()->attach($child->id);

    Product::factory()->count(2)->create()->each(
        fn (Product $product) => $product->categories()->attach($grandchild->id)
    );

    Product::factory()->create()->categories()->attach($unrelated->id);

    // descendants() computes its range from the model's own in-memory
    // _lft/_rgt - refresh() picks up the bounds as widened by the child/
    // grandchild inserts above, the same as a route-bound controller
    // fetching the category fresh would.
    $root->refresh();
    $child->refresh();
    $grandchild->refresh();
    $unrelated->refresh();

    expect($root->descendantProductsCount())->toBe(4)
        ->and($child->descendantProductsCount())->toBe(3)
        ->and($grandchild->descendantProductsCount())->toBe(2)
        ->and($unrelated->descendantProductsCount())->toBe(1);
});
