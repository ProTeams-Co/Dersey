<?php

namespace App\Support\Search;

/**
 * Without this, "فستان", "فُستان", and "فستآن" would log as three different
 * normalized_term values and every popularity/zero-results report built on
 * top of search_logs would be meaningless - Arabic input varies in ways
 * that don't change the word's meaning (diacritics, alef/ya/ta-marbuta
 * variants) but do change its raw bytes.
 */
class ArabicNormalizer
{
    public static function normalize(string $term): string
    {
        $term = trim($term);

        // Arabic diacritics (tashkeel) + tatweel (kashida).
        $term = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $term);

        // Unify alef variants (أ إ آ -> ا).
        $term = preg_replace('/[إأآ]/u', 'ا', $term);

        // Unify ya (ى -> ي).
        $term = str_replace('ى', 'ي', $term);

        // Unify ta marbuta (ة -> ه).
        $term = str_replace('ة', 'ه', $term);

        // Collapse repeated whitespace.
        $term = preg_replace('/\s+/u', ' ', $term);

        return mb_strtolower(trim($term));
    }
}
