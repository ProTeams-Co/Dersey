<?php

namespace App\Services\Seo;

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * One place that knows how hreflang/canonical are built, so no view has to
 * re-derive them. getLocalizedURL() already returns an absolute URL with the
 * target locale's prefix swapped in (and the query string preserved) — the
 * one thing this class adds on top is escaping, since that URL is built from
 * the current request's own query string, which a visitor controls.
 */
class SeoService
{
    public function tags(): string
    {
        $links = [];

        foreach (array_keys(LaravelLocalization::getSupportedLocales()) as $locale) {
            $links[] = $this->alternateLink($locale, LaravelLocalization::getLocalizedURL($locale));
        }

        $links[] = $this->alternateLink('x-default', LaravelLocalization::getLocalizedURL(LaravelLocalization::getDefaultLocale()));
        $links[] = $this->canonicalLink(LaravelLocalization::getLocalizedURL(app()->getLocale()));

        return implode(PHP_EOL, $links);
    }

    private function alternateLink(string $hreflang, string $href): string
    {
        return '<link rel="alternate" hreflang="'.e($hreflang).'" href="'.e($href).'">';
    }

    private function canonicalLink(string $href): string
    {
        return '<link rel="canonical" href="'.e($href).'">';
    }
}
