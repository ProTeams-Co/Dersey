<?php

namespace Database\Seeders;

use App\Models\SearchLog;
use App\Models\User;
use App\Support\Search\ArabicNormalizer;
use Illuminate\Database\Seeder;

class SearchLogSeeder extends Seeder
{
    public function run(): void
    {
        if (SearchLog::query()->exists()) {
            return;
        }

        $userIds = User::query()->pluck('id')->all();
        $rows = [];

        foreach ($this->searches() as [$term, $resultsCount, $repeats]) {
            for ($i = 0; $i < $repeats; $i++) {
                $rows[] = [
                    'term' => $term,
                    'normalized_term' => ArabicNormalizer::normalize($term),
                    'results_count' => $resultsCount,
                    'user_id' => $userIds === [] ? null : (fake()->boolean(50) ? $userIds[array_rand($userIds)] : null),
                    'session_id' => bin2hex(random_bytes(16)),
                    'locale' => str_contains($term, ' ') || preg_match('/\p{Arabic}/u', $term) ? 'ar' : 'en',
                    'ip' => fake()->ipv4(),
                    'created_at' => now()->subDays(random_int(0, 30))->subMinutes(random_int(0, 1440)),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            SearchLog::query()->insert($chunk);
        }
    }

    /**
     * Deliberately includes several spelling/diacritic variants of the same
     * word (فستان / فُستان / فستآن) - normalized_term should collapse them
     * into one popular term instead of three separate rows.
     *
     * @return list<array{0: string, 1: int, 2: int}>
     */
    private function searches(): array
    {
        return [
            ['فستان', 12, 8],
            ['فُستان', 12, 3],
            ['فستآن', 12, 2],
            ['قميص رجالي', 8, 6],
            ['بنطلون جينز', 15, 7],
            ['حذاء رياضي', 5, 5],
            ['جاكيت شتوي', 9, 4],
            ['تيشرت أطفال', 6, 4],
            ['dress', 12, 5],
            ['shirt', 10, 4],
            ['jeans', 15, 3],
            ['abaya قفطان', 0, 3],
            ['شنطة يد فاخرة', 0, 4],
            ['حزام جلد إيطالي', 0, 2],
            ['nonexistentitem123', 0, 2],
        ];
    }
}
