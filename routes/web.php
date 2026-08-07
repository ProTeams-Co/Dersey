<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Temporary — verifies fonts/tokens for Batch 1.2, removed in Batch 1.6.
Route::get('/design-test', function () {
    return view('design-test');
});
