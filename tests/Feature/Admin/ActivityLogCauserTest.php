<?php

use App\Enums\AdminStatus;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('attributes an automatically-logged model change to the acting admin, not no one', function () {
    $actor = Admin::factory()->create(['status' => AdminStatus::Active]);
    auth('admin')->login($actor);

    $target = Admin::factory()->create(['name' => 'Original Name']);
    $target->update(['name' => 'Updated Name']);

    $logged = Activity::where('subject_type', Admin::class)
        ->where('subject_id', $target->id)
        ->where('description', 'updated')
        ->latest('id')
        ->first();

    expect($logged)->not->toBeNull()
        ->and($logged->causer_id)->toBe($actor->id)
        ->and($logged->causer_type)->toBe(Admin::class);
});
