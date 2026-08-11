<?php

namespace App\Services\Search;

use App\Models\SearchLog;
use App\Support\Search\ArabicNormalizer;
use Illuminate\Support\Collection;

class SearchLogService
{
    public function log(
        string $term,
        int $resultsCount,
        string $locale,
        ?int $userId = null,
        ?string $sessionId = null,
        ?string $ip = null,
    ): SearchLog {
        return SearchLog::create([
            'term' => $term,
            'normalized_term' => ArabicNormalizer::normalize($term),
            'results_count' => $resultsCount,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'locale' => $locale,
            'ip' => $ip,
        ]);
    }

    /**
     * Most-searched terms, grouped by their normalized form so spelling/
     * diacritic variants of the same word count together.
     *
     * @return Collection<int, object{normalized_term: string, searches: int}>
     */
    public function popular(int $limit = 10): Collection
    {
        return SearchLog::query()
            ->selectRaw('normalized_term, COUNT(*) as searches')
            ->groupBy('normalized_term')
            ->orderByDesc('searches')
            ->limit($limit)
            ->get();
    }

    /**
     * Terms that consistently return nothing - the most direct signal of
     * missing catalog coverage or a mismatched search index.
     *
     * @return Collection<int, object{normalized_term: string, searches: int}>
     */
    public function zeroResults(int $limit = 10): Collection
    {
        return SearchLog::query()
            ->where('results_count', 0)
            ->selectRaw('normalized_term, COUNT(*) as searches')
            ->groupBy('normalized_term')
            ->orderByDesc('searches')
            ->limit($limit)
            ->get();
    }
}
