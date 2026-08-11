<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * WithoutModelEvents deliberately not used — SettingObserver and
     * AddressObserver need to actually run during seeding for the seeded
     * data to end up in a correct state (e.g. sample addresses respecting
     * "one default per user"), not just during real requests.
     */
    public function run(): void
    {
        $this->call([
            GovernorateSeeder::class,
            SettingSeeder::class,
            RolePermissionSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
