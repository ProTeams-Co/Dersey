<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Batch 3.2-C decision A's own mandatory test - the whole point of moving
 * from `code === 'color'` to Attribute::colorAttribute() (a real,
 * type-based signal) is that renaming the attribute's `code` field (a
 * plain admin-editable value, changeable from the Attributes screen) must
 * NOT break "which of a variant's attribute values is its color".
 */
it('still resolves the correct display image after the color attribute\'s code is renamed', function () {
    $product = Product::factory()->create();

    $color = Attribute::factory()->color()->create(['code' => 'renamed-not-color']);
    $red = AttributeValue::factory()->create(['attribute_id' => $color->id]);

    $redImage = ProductImage::factory()->for($product)->create(['color_value_id' => $red->id]);

    $variant = $product->variants()->create(['sku' => 'COLOR-RENAME-'.uniqid(), 'stock_quantity' => 0, 'low_stock_threshold' => 5]);
    $variant->syncOptionValues([$red->id]);

    $variant->load(['attributeValues.attribute', 'product.images']);

    expect($variant->displayImage()->id)->toBe($redImage->id);
});

it('resolves the color attribute via AttributeType::Color even when multiple color-typed attributes exist - lowest sort wins', function () {
    $lowerSort = Attribute::factory()->color()->create(['sort' => 1]);
    $higherSort = Attribute::factory()->color()->create(['sort' => 5]);

    expect(Attribute::colorAttribute()->id)->toBe($lowerSort->id)
        ->and(Attribute::colorAttribute()->id)->not->toBe($higherSort->id);
});
