<?php

namespace App\Observers;

use App\Models\Setting;
use App\Support\Cache\VersionedCache;

/**
 * Every setting is loaded into one cached blob (see SettingsService::all()
 * and CacheKeys::settings()) rather than cached per-row, so the trigger
 * for invalidation is simply "any Setting row changed" — bumping the
 * "setting" tag makes CacheKeys::settings() resolve to a new key on the
 * next read, orphaning the stale blob instead of needing to delete it.
 */
class SettingObserver
{
    public function saved(Setting $setting): void
    {
        VersionedCache::bump('setting');
    }

    public function deleted(Setting $setting): void
    {
        VersionedCache::bump('setting');
    }
}
