<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * updateOrCreate on (group, key) throughout — safe to run twice.
     */
    public function run(): void
    {
        $settings = [
            ['store', 'name', 'Dersey', 'string'],
            ['store', 'tagline_ar', 'أزياء، بالطريقة الصح.', 'string'],
            ['store', 'tagline_en', 'Fashion, done right.', 'string'],
            ['store', 'maintenance_mode', false, 'boolean'],

            ['contact', 'email', config('dersey.store.email') ?? 'support@dersey.com', 'string'],
            ['contact', 'phone', config('dersey.store.phone') ?? '19000', 'string'],
            ['contact', 'address_ar', 'القاهرة، مصر', 'string'],
            ['contact', 'address_en', 'Cairo, Egypt', 'string'],

            ['social', 'instagram', 'https://instagram.com/dersey', 'string'],
            ['social', 'facebook', 'https://facebook.com/dersey', 'string'],
            ['social', 'x', 'https://x.com/dersey', 'string'],

            // Single-currency store (CLAUDE.md) — stored as a setting anyway
            // so it renders through the same admin settings screen as
            // everything else, not because it is expected to ever change.
            ['store', 'currency', config('dersey.store.currency', 'EGP'), 'string'],
        ];

        foreach ($settings as [$group, $key, $value, $type]) {
            Setting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $value, 'type' => $type]
            );
        }
    }
}
