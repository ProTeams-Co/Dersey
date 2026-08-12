<?php

/*
|--------------------------------------------------------------------------
| Admin Sidebar Menu
|--------------------------------------------------------------------------
|
| A plain PHP array, not hand-written HTML - the sidebar partial loops over
| this and checks each item's `permission` (a spatie/laravel-permission
| ability string, matching RolePermissionSeeder's {resource}.{action}
| convention) against the current admin before rendering it, so visibility
| is data-driven rather than duplicated in markup.
|
| `route` names below may not be registered yet (categories/products/etc.
| are 3.1+, out of this batch's scope) - the sidebar partial checks
| Route::has() and renders an unlinked, disabled-looking item instead of
| calling route() on a name that doesn't exist. Every batch that adds the
| real controller only needs to register the route under the same name for
| the matching item to "light up" automatically; no menu changes needed.
|
| Only resources that RolePermissionSeeder actually seeds permissions for
| are listed here - inventing menu entries for permissions that don't
| exist yet would either bypass the permission gate or never show for
| anyone, both worse than just not listing them until they're real.
|
*/

return [
    [
        'key' => 'dashboard',
        'label' => 'admin.menu.dashboard',
        'icon' => 'dashboard',
        'route' => 'admin.dashboard',
        'permission' => null,
    ],
    [
        'key' => 'catalog',
        'label' => 'admin.menu.catalog',
        'icon' => 'shirt',
        'children' => [
            ['key' => 'products', 'label' => 'admin.menu.products', 'route' => 'admin.products.index', 'permission' => 'products.view'],
            ['key' => 'categories', 'label' => 'admin.menu.categories', 'route' => 'admin.categories.index', 'permission' => 'categories.view'],
            ['key' => 'brands', 'label' => 'admin.menu.brands', 'route' => 'admin.brands.index', 'permission' => 'brands.view'],
            ['key' => 'attributes', 'label' => 'admin.menu.attributes', 'route' => 'admin.attributes.index', 'permission' => 'attributes.view'],
            ['key' => 'inventory', 'label' => 'admin.menu.inventory', 'route' => 'admin.inventory.index', 'permission' => 'inventory.view'],
        ],
    ],
    [
        'key' => 'orders',
        'label' => 'admin.menu.orders',
        'icon' => 'shopping-cart',
        'route' => 'admin.orders.index',
        'permission' => 'orders.view',
    ],
    [
        'key' => 'customers',
        'label' => 'admin.menu.customers',
        'icon' => 'users',
        'children' => [
            ['key' => 'customers', 'label' => 'admin.menu.customers', 'route' => 'admin.customers.index', 'permission' => 'customers.view'],
            ['key' => 'addresses', 'label' => 'admin.menu.addresses', 'route' => 'admin.addresses.index', 'permission' => 'addresses.view'],
        ],
    ],
    [
        'key' => 'discounts',
        'label' => 'admin.menu.discounts',
        'icon' => 'percent',
        'route' => 'admin.discounts.index',
        'permission' => 'discounts.view',
    ],
    [
        'key' => 'settings',
        'label' => 'admin.menu.settings',
        'icon' => 'settings',
        'route' => 'admin.settings.index',
        'permission' => 'settings.view',
    ],
    [
        'key' => 'administration',
        'label' => 'admin.menu.administration',
        'icon' => 'shield',
        'children' => [
            ['key' => 'admins', 'label' => 'admin.menu.admins', 'route' => 'admin.admins.index', 'permission' => 'admins.view'],
            ['key' => 'roles', 'label' => 'admin.menu.roles', 'route' => 'admin.roles.index', 'permission' => 'roles.view'],
        ],
    ],
];
