<?php

use App\Models\Admin;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the admins index table for a super-admin with no errors', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = Admin::factory()->create();
    $admin->assignRole('super-admin');

    $response = $this->actingAs($admin, 'admin')->get(route('admin.admins.index'));

    $response->assertOk();
    $response->assertSee($admin->name);
});

it('denies a non-permitted admin from viewing the admins table', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = Admin::factory()->create();
    $admin->assignRole('support'); // has no admins.view permission

    $response = $this->actingAs($admin, 'admin')->get(route('admin.admins.index'));

    $response->assertForbidden();
});
