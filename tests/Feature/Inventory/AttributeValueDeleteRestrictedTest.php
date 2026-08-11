<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuses to hard-delete an attribute value that is still used by a variant', function () {
    $product = Product::factory()->create();
    $sizeAttribute = Attribute::factory()->variant()->create();
    $medium = AttributeValue::factory()->create(['attribute_id' => $sizeAttribute->id]);

    $variant = $product->variants()->create([
        'sku' => 'RESTRICT-TEST-'.uniqid(),
        'stock_quantity' => 0,
        'low_stock_threshold' => 5,
    ]);
    $variant->syncOptionValues([$medium->id]);

    // product_variant_values.attribute_value_id is restrictOnDelete(), and
    // that only fires on a real SQL DELETE - forceDelete(), not the
    // normal soft delete() a controller would use.
    expect(fn () => $medium->forceDelete())->toThrow(QueryException::class);

    expect(AttributeValue::withTrashed()->find($medium->id))->not->toBeNull();
});
