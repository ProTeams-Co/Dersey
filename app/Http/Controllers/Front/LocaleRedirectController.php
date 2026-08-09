<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Mcamara\LaravelLocalization\LanguageNegotiator;

/**
 * Handles the bare "/" only. 301 is deliberately never used here — see the
 * batch notes: the destination depends on Accept-Language/cookie, and a
 * permanently-cached redirect would trap a visitor on the wrong locale.
 *
 * Priority: bot -> always the default locale (one crawlable version, not a
 * moving target). Returning visitor -> their own past choice (cookie) wins
 * over whatever their browser sends this time. Otherwise -> Accept-Language.
 */
class LocaleRedirectController extends Controller
{
    private const COOKIE_NAME = 'locale';

    private const COOKIE_MINUTES = 60 * 24 * 365;

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegrambot|preview|headless/i';

    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $this->resolveLocale($request);

        return redirect(LaravelLocalization::getLocalizedURL($locale, '/'), 302, ['Vary' => 'Accept-Language'])
            ->withCookie(cookie(self::COOKIE_NAME, $locale, self::COOKIE_MINUTES));
    }

    private function resolveLocale(Request $request): string
    {
        if ($this->isBot($request)) {
            return LaravelLocalization::getDefaultLocale();
        }

        $cookieLocale = $request->cookie(self::COOKIE_NAME);
        if (is_string($cookieLocale) && LaravelLocalization::checkLocaleInSupportedLocales($cookieLocale)) {
            return $cookieLocale;
        }

        $negotiator = new LanguageNegotiator(
            LaravelLocalization::getDefaultLocale(),
            LaravelLocalization::getSupportedLocales(),
            $request
        );

        return $negotiator->negotiateLanguage();
    }

    private function isBot(Request $request): bool
    {
        return (bool) preg_match(self::BOT_PATTERN, (string) $request->userAgent());
    }
}
