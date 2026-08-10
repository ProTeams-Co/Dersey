<?php

use Illuminate\Support\Facades\Route;

// Real, permanent endpoint — core/ajax.js hits this after a 419 to mint a
// fresh token before retrying the original request once. Throttled: it's
// unauthenticated and needs no CSRF token of its own to call, so without a
// limit it would be a free, repeatable request for anyone to hammer.
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))
    ->middleware('throttle:10,1')
    ->name('csrf-token');

// TODO(1.6): remove — deliberately-failing endpoints so /js-test (also
// removed in 1.6) can exercise core/ajax.js's error handling against real
// HTTP responses instead of mocked ones.
Route::post('/test/419', fn () => response()->json(['message' => 'CSRF token mismatch (simulated).'], 419))->name('test.419');

Route::post('/test/422', fn () => response()->json([
    'message' => 'The given data was invalid.',
    'errors' => [
        'email' => ['The email field is required.'],
    ],
], 422))->name('test.422');

Route::post('/test/429', fn () => response()
    ->json(['message' => 'Too many requests (simulated).'], 429)
    ->header('Retry-After', 30))->name('test.429');

Route::post('/test/500', function () {
    throw new \RuntimeException('Deliberate test exception for Batch 1.4 verification — see /js-test.');
})->name('test.500');
