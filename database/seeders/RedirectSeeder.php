<?php

namespace Database\Seeders;

use App\Enums\RedirectStatusCode;
use App\Models\Redirect;
use Illuminate\Database\Seeder;

/**
 * A handful of realistic legacy-URL redirects, on top of whatever
 * ProductTranslationObserver/CategoryTranslationObserver generate
 * automatically on slug changes - no loops here (A -> B -> A is only ever
 * exercised in an isolated Pest test, never left in permanent seed data).
 */
class RedirectSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->redirects() as $data) {
            Redirect::query()->updateOrCreate(
                ['from_path' => $data['from']],
                [
                    'to_path' => $data['to'],
                    'status_code' => $data['code'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return list<array{from: string, to: string, code: RedirectStatusCode}>
     */
    private function redirects(): array
    {
        return [
            ['from' => '/ar/old-about', 'to' => '/ar/من-نحن', 'code' => RedirectStatusCode::Permanent],
            ['from' => '/en/old-about', 'to' => '/en/about-us', 'code' => RedirectStatusCode::Permanent],
            ['from' => '/ar/old-contact', 'to' => '/ar/اتصل-بنا', 'code' => RedirectStatusCode::Permanent],
            ['from' => '/ar/promo-2025', 'to' => '/ar/collections/summer', 'code' => RedirectStatusCode::Temporary],
        ];
    }
}
