<?php

use App\Exceptions\InsufficientStockException;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuses to reserve more than available_quantity', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'reserved_quantity' => 0]);
    $service = app(InventoryService::class);

    // Uses up all 10 - available_quantity is now 0.
    $service->reserve($variant, 10);

    expect(fn () => $service->reserve($variant, 1))
        ->toThrow(InsufficientStockException::class);

    $variant->refresh();

    expect($variant->reserved_quantity)->toBe(10)
        ->and($variant->available_quantity)->toBe(0);
});

it('refuses a reservation larger than stock even when nothing is reserved yet', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5, 'reserved_quantity' => 0]);
    $service = app(InventoryService::class);

    expect(fn () => $service->reserve($variant, 6))
        ->toThrow(InsufficientStockException::class);

    $variant->refresh();

    expect($variant->reserved_quantity)->toBe(0);
});
