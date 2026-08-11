<?php

use App\Exceptions\StaleModelException;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a stale concurrent write fail with StaleModelException instead of losing an update', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 1]);

    // Two genuinely separate reads from the database - not two references
    // to the same PHP object - mirroring what two concurrent requests
    // would each hold in memory.
    $readByRequestA = ProductVariant::find($variant->id);
    $readByRequestB = ProductVariant::find($variant->id);

    expect($readByRequestA->version)->toBe(0)
        ->and($readByRequestB->version)->toBe(0);

    $readByRequestA->stock_quantity -= 1;
    $readByRequestA->saveWithVersion();

    expect($readByRequestA->stock_quantity)->toBe(0)
        ->and($readByRequestA->version)->toBe(1);

    // B still holds version 0 in memory - its conditional UPDATE now
    // matches zero rows.
    expect(function () use ($readByRequestB) {
        $readByRequestB->stock_quantity -= 1;
        $readByRequestB->saveWithVersion();
    })->toThrow(StaleModelException::class);

    $final = ProductVariant::find($variant->id);

    expect($final->stock_quantity)->toBe(0) // not -1
        ->and($final->version)->toBe(1);
});

it('does not bump the version when saveWithVersion() is called on an unchanged model', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $fresh = ProductVariant::find($variant->id);

    // Nothing is dirty - this must be a genuine no-op, not a write that
    // bumps `version` for no reason and invalidates any other request
    // that already read this same row.
    $fresh->saveWithVersion();

    expect($fresh->version)->toBe(0);

    $stillInDatabase = ProductVariant::find($variant->id);
    expect($stillInDatabase->version)->toBe(0);
});
