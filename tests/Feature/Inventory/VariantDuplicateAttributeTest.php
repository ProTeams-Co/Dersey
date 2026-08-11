<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuses a variant with two values from the same attribute', function () {
    $product = Product::factory()->create();
    $sizeAttribute = Attribute::factory()->variant()->create();
    $small = AttributeValue::factory()->create(['attribute_id' => $sizeAttribute->id]);
    $medium = AttributeValue::factory()->create(['attribute_id' => $sizeAttribute->id]);

    $variant = $product->variants()->create([
        'sku' => 'DUP-TEST-'.uniqid(),
        'stock_quantity' => 0,
        'low_stock_threshold' => 5,
    ]);

    expect(fn () => $variant->syncOptionValues([$small->id, $medium->id]))
        ->toThrow(InvalidArgumentException::class);
});

it('refuses a value from a non-variant (filter-only) attribute on a variant', function () {
    $product = Product::factory()->create();
    $materialAttribute = Attribute::factory()->create(['is_variant' => false]);
    $cotton = AttributeValue::factory()->create(['attribute_id' => $materialAttribute->id]);

    $variant = $product->variants()->create([
        'sku' => 'NONVAR-TEST-'.uniqid(),
        'stock_quantity' => 0,
        'low_stock_threshold' => 5,
    ]);

    expect(fn () => $variant->syncOptionValues([$cotton->id]))
        ->toThrow(InvalidArgumentException::class);
});
