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
            CategorySeeder::class,
            BrandSeeder::class,
            AttributeSeeder::class,
            ProductSeeder::class,
            VariantSeeder::class,
            ShippingSeeder::class,
            CouponSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Needs at least one real User to exist (the Test User above, or
        // any from earlier seeders) - runs last since it depends on
        // products/variants/shipping methods all being seeded already.
        $this->call([
            OrderSeeder::class,
        ]);
    }
}
