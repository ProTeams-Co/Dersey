<?php

use Illuminate\Support\Facades\Route;

// TODO(3.0): remove — temporary proof that /admin sits outside the locale
// system (no {locale} prefix, no redirect, no SetLocale middleware). Replace
// with the real admin routes in Batch 3.0.
Route::get('/', fn () => response('admin ok', 200))->name('admin.ping');
