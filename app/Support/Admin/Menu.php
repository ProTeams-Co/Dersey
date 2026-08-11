<?php

namespace App\Support\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Resolves config('admin-menu') into the subset the current admin may
 * actually see, with route existence/active-state already worked out - the
 * sidebar partial just loops over the result, no permission or Route::has()
 * checks in the view itself.
 */
class Menu
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function visible(): array
    {
        return collect(config('admin-menu'))
            ->map(fn (array $item) => self::resolveItem($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private static function resolveItem(array $item): ?array
    {
        if (isset($item['children'])) {
            $children = collect($item['children'])
                ->map(fn (array $child) => self::resolveItem($child))
                ->filter()
                ->values()
                ->all();

            if ($children === []) {
                return null;
            }

            $item['children'] = $children;
            $item['active'] = collect($children)->contains('active', true);

            return $item;
        }

        if (! self::authorized($item['permission'] ?? null)) {
            return null;
        }

        $item['exists'] = Route::has($item['route']);
        $item['url'] = $item['exists'] ? route($item['route']) : null;
        $item['active'] = $item['exists'] && Route::currentRouteName() === $item['route'];

        return $item;
    }

    private static function authorized(?string $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        $admin = Auth::guard('admin')->user();

        return $admin !== null && $admin->can($permission);
    }
}
