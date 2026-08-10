<?php

use App\Http\Controllers\Front\LocaleRedirectController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

// Bare root only — custom 302 to /ar or /en. Not handled by the package's
// own hideDefaultLocaleInURL redirect (disabled — see config/laravellocalization.php).
Route::get('/', LocaleRedirectController::class)->name('root');

/**
 * One group per supported locale, with a static prefix ('ar'/'en') — NOT
 * LaravelLocalization::setLocale() called bare as the prefix value. That
 * method has a real quirk: called with no argument, it correctly sets the
 * package's internal locale but returns an EMPTY string as its return value
 * whenever the current request has no locale URL segment yet (exactly the
 * bare "/" case) — which silently registered "home" under an empty prefix
 * and collided with the root route above. Looping over known-valid locale
 * strings sidesteps that entirely, and as a side effect gives us the
 * "/xx/anything -> 404" requirement for free: only /ar/* and /en/* are ever
 * registered, so anything else is simply unmatched by any route.
 *
 * SetLocale (app/Http/Middleware/SetLocale.php) is what actually calls
 * LaravelLocalization::setLocale($locale) — WITH an explicit, already-valid
 * argument this time, which avoids the same quirk (see that file).
 */
foreach (array_keys(LaravelLocalization::getSupportedLocales()) as $locale) {
    Route::group([
        'prefix' => $locale,
        'middleware' => ['localizationRedirect', 'localizationViewPath', SetLocale::class],
    ], function () {
        Route::get('/', function () {
            return view('welcome');
        })->name('home');

        // Temporary — verifies fonts/tokens for Batch 1.2, removed in Batch 1.6.
        Route::get('/design-test', function () {
            return view('design-test');
        })->name('design-test');

        // Temporary — verifies JS infrastructure for Batch 1.4, removed in Batch 1.6.
        Route::get('/js-test', function () {
            return view('js-test');
        })->name('js-test');
    });
}
