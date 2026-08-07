<?php

namespace App\Support\Cache;

/**
 * The only place cache key strings are allowed to be built. Never inline a
 * cache key string anywhere else in the app.
 */
final class CacheKeys
{
    public static function categoryTree(string $locale): string
    {
        return VersionedCache::key('category', "tree:{$locale}");
    }

    public static function navigation(string $locale): string
    {
        return VersionedCache::key('navigation', $locale);
    }

    public static function settings(): string
    {
        return VersionedCache::key('setting', 'all');
    }

    public static function homeSections(string $locale): string
    {
        return VersionedCache::key('home', "sections:{$locale}");
    }

    public static function product(int $id, string $locale): string
    {
        return VersionedCache::key('product', "{$id}:{$locale}");
    }

    public static function facets(int $categoryId): string
    {
        return VersionedCache::key('category', "facets:{$categoryId}");
    }
}
