<?php

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('unsets the previous default address when a new one is marked default', function () {
    $user = User::factory()->create();

    $first = Address::factory()->for($user)->default()->create();
    expect($first->refresh()->is_default)->toBeTrue();

    $second = Address::factory()->for($user)->default()->create();

    expect($second->is_default)->toBeTrue()
        ->and($first->refresh()->is_default)->toBeFalse();

    // Exactly one default, never zero or two.
    expect($user->addresses()->where('is_default', true)->count())->toBe(1);
});

it('does not touch another user default address when swapping', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $addressA = Address::factory()->for($userA)->default()->create();
    $addressB = Address::factory()->for($userB)->default()->create();

    Address::factory()->for($userA)->default()->create();

    expect($addressA->refresh()->is_default)->toBeFalse()
        ->and($addressB->refresh()->is_default)->toBeTrue();
});
