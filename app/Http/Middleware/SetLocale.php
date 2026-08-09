<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

/**
 * The route group prefix (routes/web.php) is a static 'ar'/'en' string, not
 * a LaravelLocalization::setLocale() call — that method, called with no
 * argument, correctly sets its internal locale but returns an EMPTY string
 * as the prefix whenever the request has no locale segment yet (the bare
 * "/" case), which broke route registration. So nothing upstream of this
 * middleware has told the package (or the app) which locale actually
 * matched. This middleware does that: it reads the locale straight off the
 * matched route's own first segment and calls setLocale() WITH that
 * explicit, already-valid argument — which takes the method's "already
 * supported" branch and avoids the empty-return quirk entirely.
 */
class SetLocale
{
    private const COOKIE_NAME = 'locale';

    private const COOKIE_MINUTES = 60 * 24 * 365;

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->segment(1);

        LaravelLocalization::setLocale($locale);
        Carbon::setLocale($locale);

        // Try several candidate names — the exact locale identifiers
        // available differ by OS/distro, and setlocale() silently returns
        // false instead of erroring when a candidate isn't installed.
        setlocale(LC_TIME, ...($locale === 'ar'
            ? ['ar_EG.UTF-8', 'ar_EG', 'Arabic_Egypt.1256', 'ar']
            : ['en_GB.UTF-8', 'en_GB', 'English_United Kingdom.1252', 'en']));

        $response = $next($request);

        return $response->withCookie(
            cookie(self::COOKIE_NAME, $locale, self::COOKIE_MINUTES)
        );
    }
}
