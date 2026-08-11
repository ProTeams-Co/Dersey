<?php

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reads settings from the cache instead of querying on every call', function () {
    Setting::create(['group' => 'store', 'key' => 'name', 'value' => 'Dersey', 'type' => 'string']);

    $service = app(SettingsService::class);
    expect($service->get('store.name'))->toBe('Dersey');

    // A direct write that bypasses the Eloquent model (and therefore
    // SettingObserver) entirely - if the service is truly cache-backed
    // rather than re-querying, it must still return the cached value.
    Setting::withoutEvents(function () {
        Setting::where('group', 'store')->where('key', 'name')->update(['value' => 'Changed Behind The Cache']);
    });

    expect($service->get('store.name'))->toBe('Dersey');
});

it('has SettingObserver invalidate the cache on every write, through the model or the service', function () {
    $setting = Setting::create(['group' => 'store', 'key' => 'name', 'value' => 'Dersey', 'type' => 'string']);

    $service = app(SettingsService::class);
    expect($service->get('store.name'))->toBe('Dersey');

    $setting->update(['value' => 'Updated Via Model']);
    expect($service->get('store.name'))->toBe('Updated Via Model');

    $service->set('store.name', 'Updated Via Service');
    expect($service->get('store.name'))->toBe('Updated Via Service');
});

it('returns the given default when a setting does not exist', function () {
    $service = app(SettingsService::class);

    expect($service->get('store.missing_key', 'fallback'))->toBe('fallback');
});
