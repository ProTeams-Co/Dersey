<?php

use App\Models\Address;
use App\Models\Governorate;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuses to delete a governorate that has addresses referencing it', function () {
    $address = Address::factory()->create();
    $governorate = Governorate::findOrFail($address->governorate_id);

    expect(fn () => $governorate->delete())->toThrow(QueryException::class);

    expect(Governorate::find($governorate->id))->not->toBeNull();
});

it('refuses to delete a governorate that still has cities, even with no addresses', function () {
    $governorate = Governorate::factory()->has(\App\Models\City::factory())->create();

    expect(fn () => $governorate->delete())->toThrow(QueryException::class);
});
