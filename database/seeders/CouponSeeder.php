<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::create([
            'code' => 'WELCOME10',
            'type' => DiscountType::Percent,
            'value' => 10,
            'min_order_amount' => null,
            'max_discount_amount' => 20000, // capped at 200 EGP
            'usage_limit' => null,
            'usage_limit_per_user' => 1,
            'used_count' => 0,
            'first_order_only' => true,
            'starts_at' => null,
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'SAVE50',
            'type' => DiscountType::Fixed,
            'value' => 5000, // 50 EGP off
            'min_order_amount' => 50000, // on orders 500 EGP+
            'max_discount_amount' => null,
            'usage_limit' => 500,
            'usage_limit_per_user' => 3,
            'used_count' => 0,
            'first_order_only' => false,
            'starts_at' => null,
            'expires_at' => now()->addMonths(6),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'FREESHIP',
            'type' => DiscountType::FreeShipping,
            'value' => 0,
            'min_order_amount' => 30000, // 300 EGP+
            'max_discount_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'used_count' => 0,
            'first_order_only' => false,
            'starts_at' => null,
            'expires_at' => now()->addMonths(3),
            'is_active' => true,
        ]);

        // Expired - proves validate() actually rejects a real expired row,
        // not just a hypothetical one.
        Coupon::create([
            'code' => 'SUMMER24',
            'type' => DiscountType::Percent,
            'value' => 20,
            'min_order_amount' => null,
            'max_discount_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'used_count' => 12,
            'first_order_only' => false,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subMonths(2),
            'is_active' => true,
        ]);

        // Exhausted - usage_limit already fully used.
        Coupon::create([
            'code' => 'FLASH20',
            'type' => DiscountType::Percent,
            'value' => 20,
            'min_order_amount' => null,
            'max_discount_amount' => 10000,
            'usage_limit' => 50,
            'usage_limit_per_user' => null,
            'used_count' => 50,
            'first_order_only' => false,
            'starts_at' => now()->subWeek(),
            'expires_at' => now()->addWeek(),
            'is_active' => true,
        ]);
    }
}
