<?php

namespace Database\Seeders;

use App\Enums\ShippingMethodType;
use App\Models\Governorate;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

/**
 * 4 real zones covering all 27 governorates seeded in Batch 2.1
 * (GovernorateSeeder) - every governorate ends up assigned to exactly one
 * zone, matching the approved "direct shipping_zone_id column" decision
 * (no governorate_shipping_zone pivot).
 */
class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->zones() as $sort => $definition) {
            $zone = ShippingZone::create([
                'name' => ['ar' => $definition['name_ar'], 'en' => $definition['name_en']],
                'is_active' => true,
                'sort' => $sort,
            ]);

            Governorate::whereIn('code', $definition['governorate_codes'])->update(['shipping_zone_id' => $zone->id]);

            foreach ($definition['methods'] as $methodSort => $method) {
                ShippingMethod::create([
                    'zone_id' => $zone->id,
                    'name' => ['ar' => $method['name_ar'], 'en' => $method['name_en']],
                    'description' => null,
                    'type' => $method['type'],
                    'cost' => $method['cost'],
                    'free_over_amount' => $method['free_over_amount'] ?? null,
                    'cost_per_kg' => $method['cost_per_kg'] ?? null,
                    'min_days' => $method['min_days'],
                    'max_days' => $method['max_days'],
                    'is_active' => true,
                    'sort' => $methodSort,
                ]);
            }
        }
    }

    /**
     * @return array<int, array{name_ar: string, name_en: string, governorate_codes: array<int, string>, methods: array}>
     */
    private function zones(): array
    {
        return [
            [
                'name_ar' => 'القاهرة والجيزة',
                'name_en' => 'Cairo & Giza',
                'governorate_codes' => ['CAI', 'GIZ'],
                'methods' => [
                    ['name_ar' => 'توصيل عادي', 'name_en' => 'Standard Delivery', 'type' => ShippingMethodType::Flat, 'cost' => 3000, 'min_days' => 1, 'max_days' => 2],
                    ['name_ar' => 'شحن مجاني فوق 1000 جنيه', 'name_en' => 'Free Over 1000 EGP', 'type' => ShippingMethodType::FreeOver, 'cost' => 3000, 'free_over_amount' => 100000, 'min_days' => 1, 'max_days' => 2],
                ],
            ],
            [
                'name_ar' => 'الدلتا',
                'name_en' => 'Delta',
                'governorate_codes' => ['ALX', 'QLY', 'PTS', 'SUZ', 'DAM', 'DKH', 'SHR', 'GHR', 'MNF', 'BEH', 'KFS', 'ISM'],
                'methods' => [
                    ['name_ar' => 'توصيل عادي', 'name_en' => 'Standard Delivery', 'type' => ShippingMethodType::Flat, 'cost' => 5000, 'min_days' => 2, 'max_days' => 4],
                    ['name_ar' => 'شحن مجاني فوق 1500 جنيه', 'name_en' => 'Free Over 1500 EGP', 'type' => ShippingMethodType::FreeOver, 'cost' => 5000, 'free_over_amount' => 150000, 'min_days' => 2, 'max_days' => 4],
                ],
            ],
            [
                'name_ar' => 'الصعيد',
                'name_en' => 'Upper Egypt',
                'governorate_codes' => ['FYM', 'BNS', 'MNY', 'ASY', 'SOH', 'QNA', 'LXR', 'ASN'],
                'methods' => [
                    ['name_ar' => 'توصيل عادي', 'name_en' => 'Standard Delivery', 'type' => ShippingMethodType::Flat, 'cost' => 7000, 'min_days' => 3, 'max_days' => 6],
                    ['name_ar' => 'شحن حسب الوزن', 'name_en' => 'Weight-Based Shipping', 'type' => ShippingMethodType::WeightBased, 'cost' => 5000, 'cost_per_kg' => 1500, 'min_days' => 3, 'max_days' => 6],
                ],
            ],
            [
                'name_ar' => 'المناطق النائية',
                'name_en' => 'Remote Areas',
                'governorate_codes' => ['RSA', 'NVL', 'MAT', 'NSI', 'SSI'],
                'methods' => [
                    ['name_ar' => 'توصيل عادي', 'name_en' => 'Standard Delivery', 'type' => ShippingMethodType::Flat, 'cost' => 12000, 'min_days' => 5, 'max_days' => 10],
                ],
            ],
        ];
    }
}
