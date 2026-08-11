<?php

use App\Exceptions\CategoryHasDependentsException;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuses to delete a category that still has products directly assigned to it', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    expect($category->canBeDeleted())->toBeFalse()
        ->and($category->deletionBlockers())->toBe(['errors.category_has_products']);

    expect(fn () => $category->delete())->toThrow(CategoryHasDependentsException::class);
    expect(Category::find($category->id))->not->toBeNull();

    $category->products()->detach($product->id);
    $category->refresh();

    expect($category->canBeDeleted())->toBeTrue();

    $category->delete();

    expect(Category::find($category->id))->toBeNull();
});

it('reports both blockers when a category has live children AND products of its own', function () {
    $parent = Category::factory()->create();
    Category::factory()->child($parent)->create();

    $product = Product::factory()->create();
    $product->categories()->attach($parent->id);

    expect($parent->deletionBlockers())->toBe([
        'errors.category_has_children',
        'errors.category_has_products',
    ]);
});
