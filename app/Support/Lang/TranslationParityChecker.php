<?php

namespace App\Support\Lang;

/**
 * Flattens every lang/{locale}/*.php file into dot-notation keys and diffs
 * ar against en, so a translator adding a key to one file and forgetting
 * the other fails a test instead of shipping a literal key string (or a
 * silent fallback) to production.
 */
class TranslationParityChecker
{
    /**
     * @return array{missing_in_en: list<string>, missing_in_ar: list<string>}
     */
    public static function diff(): array
    {
        $ar = static::keysFor('ar');
        $en = static::keysFor('en');

        return [
            'missing_in_en' => array_values(array_diff($ar, $en)),
            'missing_in_ar' => array_values(array_diff($en, $ar)),
        ];
    }

    /**
     * @return list<string>
     */
    private static function keysFor(string $locale): array
    {
        $keys = [];

        foreach (glob(lang_path($locale).'/*.php') as $file) {
            $namespace = pathinfo($file, PATHINFO_FILENAME);

            foreach (static::flatten(require $file) as $key) {
                $keys[] = $namespace.'.'.$key;
            }
        }

        sort($keys);

        return $keys;
    }

    /**
     * @return list<string>
     */
    private static function flatten(array $lines, string $prefix = ''): array
    {
        $keys = [];

        foreach ($lines as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $keys = [...$keys, ...static::flatten($value, $fullKey)];
            } else {
                $keys[] = $fullKey;
            }
        }

        return $keys;
    }
}
