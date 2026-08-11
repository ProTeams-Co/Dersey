<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('flags a product on sale and computes its discount percentage only when compare_at_price beats base_price', function () {
    $onSale = Product::factory()->create(['base_price' => 10000, 'compare_at_price' => 12500]);
    $noComparePrice = Product::factory()->create(['base_price' => 10000, 'compare_at_price' => null]);
    $equalPrices = Product::factory()->create(['base_price' => 10000, 'compare_at_price' => 10000]);

    expect($onSale->isOnSale())->toBeTrue()
        ->and($onSale->discount_percentage)->toBe(20)
        ->and($noComparePrice->isOnSale())->toBeFalse()
        ->and($noComparePrice->discount_percentage)->toBeNull()
        ->and($equalPrices->isOnSale())->toBeFalse()
        ->and($equalPrices->discount_percentage)->toBeNull();
});
