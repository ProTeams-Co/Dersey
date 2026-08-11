<?php

use App\Models\Admin;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an admin account with the given role via the console', function () {
    $this->seed(RolePermissionSeeder::class);

    $this->artisan('admin:create', [
        '--name' => 'Test Admin',
        '--email' => 'console-admin@example.com',
        '--password' => 'password123',
        '--role' => 'manager',
    ])->assertSuccessful();

    $admin = Admin::where('email', 'console-admin@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('manager'))->toBeTrue();
});

it('fails validation for a duplicate email instead of creating a second account', function () {
    $this->seed(RolePermissionSeeder::class);
    Admin::factory()->create(['email' => 'dup@example.com']);

    $this->artisan('admin:create', [
        '--name' => 'Dup',
        '--email' => 'dup@example.com',
        '--password' => 'password123',
        '--role' => 'manager',
    ])->assertFailed();

    expect(Admin::where('email', 'dup@example.com')->count())->toBe(1);
});
