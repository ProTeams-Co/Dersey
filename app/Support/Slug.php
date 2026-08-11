<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Arabic slugs keep real Arabic letters, not a transliterated-to-Latin
 * romanization — Str::slug()'s ASCII transliteration step is what would
 * strip them, and skipping it (language: null) still runs the rest of the
 * pipeline (lowercasing, punctuation stripping, separator collapsing)
 * correctly, since \pL (Unicode "letter") matches Arabic letters too, not
 * just Latin ones. Confirmed empirically, not assumed: Str::slug($text,
 * '-', null) on Arabic input produces a clean hyphenated Arabic slug.
 */
class Slug
{
    public static function generate(string $text, string $locale): string
    {
        $language = $locale === 'ar' ? null : 'en';

        return Str::slug($text, '-', $language);
    }

    /**
     * Appends -2, -3, ... until $existsCheck (scoped by the caller to a
     * specific translation table/locale/current-row-exclusion) reports the
     * candidate as free.
     */
    public static function unique(string $base, callable $existsCheck): string
    {
        $slug = $base;
        $suffix = 2;

        while ($existsCheck($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
