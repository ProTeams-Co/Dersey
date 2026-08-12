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

    /**
     * The SQL-side counterpart of normalize() - mirrors the alef/ya/
     * ta-marbuta rules (not diacritic-stripping; stored catalog text
     * essentially never contains tashkeel, so there's nothing in the
     * column for it to strip) as nested REPLACE() calls, so a stored
     * "فستان" matches a search for "فستأن" without a separate normalized
     * column. Shared by AdminTable's translated-column search and any
     * other query built directly against a translation table (e.g. the
     * category tree's own search) - extracted here instead of living
     * only inside AdminTable so non-AdminTable query code doesn't have to
     * duplicate it.
     *
     * $column must be a trusted, hardcoded identifier (a column name from
     * application code, never raw user input) - it is interpolated
     * directly into the generated SQL.
     */
    public static function sqlExpression(string $column): string
    {
        $expr = "`{$column}`";

        foreach (['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه'] as $from => $to) {
            $expr = "REPLACE({$expr}, '{$from}', '{$to}')";
        }

        return "LOWER({$expr})";
    }
}
