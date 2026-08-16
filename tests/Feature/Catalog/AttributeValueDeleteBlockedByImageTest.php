<?php

use App\Exceptions\AttributeValueInUseException;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuses to delete a color value that is only used by a product image (not any variant)', function () {
    $colorAttribute = Attribute::factory()->color()->create();
    $red = AttributeValue::factory()->create(['attribute_id' => $colorAttribute->id]);
    $product = Product::factory()->create();

    ProductImage::factory()->for($product)->create(['color_value_id' => $red->id]);

    expect($red->isUsedByVariants())->toBeFalse()
        ->and($red->isUsedByProductImages())->toBeTrue()
        ->and($red->canBeDeleted())->toBeFalse()
        ->and($red->deletionBlockers())->toBe(['errors.attribute_value_used_in_images']);

    expect(fn () => $red->delete())->toThrow(AttributeValueInUseException::class);
    expect(AttributeValue::find($red->id))->not->toBeNull();
});

it('reports both blockers when a value is used by a variant AND pictured in the gallery', function () {
    $colorAttribute = Attribute::factory()->color()->create();
    $red = AttributeValue::factory()->create(['attribute_id' => $colorAttribute->id]);
    $product = Product::factory()->create();

    $variant = $product->variants()->create(['sku' => 'IMG-BLOCK-'.uniqid(), 'stock_quantity' => 0, 'low_stock_threshold' => 5]);
    $variant->syncOptionValues([$red->id]);
    ProductImage::factory()->for($product)->create(['color_value_id' => $red->id]);

    expect($red->deletionBlockers())->toBe([
        'errors.attribute_value_in_use',
        'errors.attribute_value_used_in_images',
    ]);
});

it('allows deleting a color value once it is no longer pictured in the gallery', function () {
    $colorAttribute = Attribute::factory()->color()->create();
    $red = AttributeValue::factory()->create(['attribute_id' => $colorAttribute->id]);
    $product = Product::factory()->create();

    $image = ProductImage::factory()->for($product)->create(['color_value_id' => $red->id]);
    $image->delete();
    $red->refresh();

    expect($red->canBeDeleted())->toBeTrue();

    $red->delete();

    expect(AttributeValue::find($red->id))->toBeNull();
});
