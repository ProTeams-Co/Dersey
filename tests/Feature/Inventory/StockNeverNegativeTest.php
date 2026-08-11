<?php

use App\Exceptions\InsufficientStockException;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('never lets stock_quantity go negative through adjust()', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 3]);
    $service = app(InventoryService::class);

    expect(fn () => $service->adjust($variant, -5, \App\Enums\InventoryMovementType::Out))
        ->toThrow(InsufficientStockException::class);

    expect($variant->fresh()->stock_quantity)->toBe(3);
});

it('never lets stock_quantity go negative through commit()', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 3, 'reserved_quantity' => 3]);
    $service = app(InventoryService::class);

    expect(fn () => $service->commit($variant, 4))
        ->toThrow(InsufficientStockException::class);

    expect($variant->fresh()->stock_quantity)->toBe(3);
});
