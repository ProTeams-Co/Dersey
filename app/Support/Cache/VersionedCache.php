<?php

namespace App\Support\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Every cache key built through CacheKeys embeds a per-tag version number
 * from here. Bumping the version (via HasVersionedCache) makes every key
 * derived from that tag stale at once, without ever touching other tags —
 * so Cache::flush() is never needed for targeted invalidation.
 */
final class VersionedCache
{
    public static function version(string $tag): int
    {
        return (int) Cache::get(self::versionKey($tag), 1);
    }

    public static function bump(string $tag): void
    {
        Cache::add(self::versionKey($tag), 1);
        Cache::increment(self::versionKey($tag));
    }

    public static function key(string $tag, string $suffix): string
    {
        return sprintf('%s:v%d:%s', $tag, self::version($tag), $suffix);
    }

    private static function versionKey(string $tag): string
    {
        return "cache_version:{$tag}";
    }
}
