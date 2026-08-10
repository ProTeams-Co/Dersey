<?php

use Illuminate\Support\Facades\Route;

// Real, permanent endpoint — core/ajax.js hits this after a 419 to mint a
// fresh token before retrying the original request once. Throttled: it's
// unauthenticated and needs no CSRF token of its own to call, so without a
// limit it would be a free, repeatable request for anyone to hammer.
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))
    ->middleware('throttle:10,1')
    ->name('csrf-token');
