<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * {resource}.{action} permissions for every admin-panel resource this
     * foundation batch anticipates — the resources themselves (products,
     * orders, ...) don't have tables yet, but the permission strings they
     * will need do, so later batches only add UI/enforcement, not the
     * access-control data itself.
     */
    private const RESOURCES = [
        'products', 'categories', 'orders', 'customers',
        'addresses', 'discounts', 'inventory', 'settings', 'admins', 'roles',
    ];

    private const ACTIONS = ['view', 'create', 'update', 'delete'];

    public function run(): void
    {
        // spatie/permission caches roles/permissions in Redis, which
        // migrate:fresh does not touch — without this, re-running
        // migrate:fresh --seed reads a previous run's now-invalid cached
        // IDs (the tables were just dropped and recreated) instead of the
        // fresh ones this run is about to create.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->createPermissions();
        $this->createRoles();
        $this->createSuperAdmin();
    }

    private function createPermissions(): void
    {
        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                Permission::findOrCreate("{$resource}.{$action}", self::GUARD);
            }
        }
    }

    private function createRoles(): void
    {
        $all = Permission::where('guard_name', self::GUARD)->pluck('name');

        $superAdmin = Role::findOrCreate('super-admin', self::GUARD);
        $superAdmin->syncPermissions($all);

        // Everything except the two resources that manage access control
        // itself (roles, admins) — prevents an "admin" from creating a
        // more-privileged account or granting itself new permissions.
        $admin = Role::findOrCreate('admin', self::GUARD);
        $admin->syncPermissions($all->reject(
            fn (string $name) => str_starts_with($name, 'roles.') || str_starts_with($name, 'admins.')
        ));

        $managerResources = ['products', 'categories', 'orders', 'customers', 'addresses', 'discounts', 'inventory'];
        $manager = Role::findOrCreate('manager', self::GUARD);
        $manager->syncPermissions($all->filter(function (string $name) use ($managerResources) {
            [$resource, $action] = explode('.', $name);

            return in_array($resource, $managerResources, true) && $action !== 'delete';
        }));

        $support = Role::findOrCreate('support', self::GUARD);
        $support->syncPermissions([
            'orders.view', 'orders.update', 'customers.view', 'addresses.view',
        ]);
    }

    /**
     * Skips creating the account (rather than falling back to a guessable
     * default password) when SUPER_ADMIN_EMAIL or SUPER_ADMIN_PASSWORD is
     * left empty — a fresh clone's .env won't have these set until someone
     * deliberately configures them.
     */
    private function createSuperAdmin(): void
    {
        $email = config('dersey.super_admin.email');
        $password = config('dersey.super_admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('Skipped super-admin creation — set SUPER_ADMIN_EMAIL/SUPER_ADMIN_PASSWORD in .env.');

            return;
        }

        $admin = Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('dersey.super_admin.name'),
                'password' => Hash::make($password),
                'status' => 'active',
            ]
        );

        // Role/permission lookups are cached by the package itself
        // (config('permission.cache')) — forget it here so assigning the
        // role to a freshly (re)created admin is never served a stale list
        // from a previous seeder run.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (! $admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }
    }
}
