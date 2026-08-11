<?php

use App\Enums\AdminStatus;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs a suspended admin straight back out when they hit a protected route with a live session', function () {
    $admin = Admin::factory()->create(['status' => AdminStatus::Inactive]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
    $this->assertGuest('admin');
});

it('lets an active admin reach the dashboard', function () {
    $admin = Admin::factory()->create(['status' => AdminStatus::Active]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response->assertOk();
});
