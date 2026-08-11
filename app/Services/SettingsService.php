<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Cache\CacheKeys;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Every setting is loaded once and kept in one cached collection, keyed
 * "group.key" -> value — a ->get() call is a plain in-memory lookup after
 * the first one, never a per-key query. SettingObserver invalidates the
 * whole blob (via VersionedCache) on any write; Cache::flexible gives it
 * stale-while-revalidate so a cache miss never blocks a request on a
 * synchronous rebuild.
 */
class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        [$group, $settingKey] = $this->splitKey($key);

        Setting::updateOrCreate(
            ['group' => $group, 'key' => $settingKey],
            ['value' => $value, 'type' => $this->inferType($value)]
        );
    }

    protected function all(): Collection
    {
        return Cache::flexible(CacheKeys::settings(), [3600, 86400], function () {
            return Setting::all()->mapWithKeys(
                fn (Setting $setting) => ["{$setting->group}.{$setting->key}" => $setting->value]
            );
        });
    }

    /**
     * "store.name" -> ['store', 'name']. A bare key with no dot is treated
     * as belonging to a "general" group rather than rejected — group is a
     * storage/organization detail the caller shouldn't always have to know.
     */
    protected function splitKey(string $key): array
    {
        return str_contains($key, '.') ? explode('.', $key, 2) : ['general', $key];
    }

    protected function inferType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}
