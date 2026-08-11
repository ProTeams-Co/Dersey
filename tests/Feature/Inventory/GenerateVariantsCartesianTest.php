<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates the full cartesian product of the given attributes - 3 sizes x 4 colors = 12', function () {
    $product = Product::factory()->create();

    $sizeAttribute = Attribute::factory()->variant()->create();
    AttributeValue::factory()->count(3)->create(['attribute_id' => $sizeAttribute->id]);

    $colorAttribute = Attribute::factory()->variant()->create();
    AttributeValue::factory()->count(4)->create(['attribute_id' => $colorAttribute->id]);

    $variants = $product->generateVariants([$sizeAttribute->id, $colorAttribute->id]);

    expect($variants)->toHaveCount(12);

    $uniqueCombinations = $variants
        ->map(fn ($variant) => $variant->attributeValues()->pluck('attribute_value_id')->sort()->values()->implode(','))
        ->unique();

    expect($uniqueCombinations)->toHaveCount(12)
        ->and($variants->pluck('sku')->unique())->toHaveCount(12);
});

it('rejects generateVariants() for a non-variant (filter-only) attribute', function () {
    $product = Product::factory()->create();
    $materialAttribute = Attribute::factory()->create(['is_variant' => false]);
    AttributeValue::factory()->count(2)->create(['attribute_id' => $materialAttribute->id]);

    expect(fn () => $product->generateVariants([$materialAttribute->id]))
        ->toThrow(InvalidArgumentException::class);
});
