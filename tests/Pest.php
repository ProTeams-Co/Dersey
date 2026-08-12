<?php

use App\Enums\AdminStatus;
use App\Models\Admin;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Shared by every Batch 3.1 admin CRUD test (Brands/Categories/Attributes) -
 * seeds the real role/permission set via RolePermissionSeeder rather than
 * faking permissions, so a test failure here would also catch a seeder
 * regression, not just a controller one.
 */
function actingAdminWithRole(string $role = 'super-admin'): Admin
{
    test()->seed(RolePermissionSeeder::class);
    $admin = Admin::factory()->create(['status' => AdminStatus::Active]);
    $admin->assignRole($role);
    test()->actingAs($admin, 'admin');

    return $admin;
}
