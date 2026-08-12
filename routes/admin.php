<?php

use App\Http\Controllers\Admin\AdminsController;
use App\Http\Controllers\Admin\AttributesController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\BrandsController;
use App\Http\Controllers\Admin\CategoriesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaUploadController;
use Illuminate\Support\Facades\Route;

// This file is already registered under prefix('admin')->name('admin.') in
// bootstrap/app.php - route names below are bare ("login", "dashboard", ...)
// and resolve to "admin.login", "admin.dashboard", etc. Replaces the old
// TODO(3.0) ping stub (and its admin.admin.ping double-prefix quirk).

Route::middleware('admin.guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('admin.auth')
    ->name('logout');

Route::middleware(['admin.auth', 'admin.active'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Temporary upload endpoint shared by admin/media.js (FilePond) and
    // admin/editor.js (CKEditor 5's upload adapter) - see
    // MediaUploadController's docblock for why this is gated by
    // admin.auth/admin.active rather than a Policy.
    Route::post('media', [MediaUploadController::class, 'store'])->name('media.store');
    Route::delete('media/{file}', [MediaUploadController::class, 'destroy'])->name('media.destroy');

    // Index-only demo/verification surface for AdminTable + AdminController
    // (see AdminsController's docblock) - not real admin-account CRUD.
    Route::get('admins', [AdminsController::class, 'index'])->name('admins.index');

    Route::prefix('brands')->name('brands.')->group(function (): void {
        Route::get('/', [BrandsController::class, 'index'])->name('index');
        Route::get('create', [BrandsController::class, 'create'])->name('create');
        Route::post('/', [BrandsController::class, 'store'])->name('store');
        Route::post('bulk-action', [BrandsController::class, 'bulkAction'])->name('bulk-action');
        Route::get('{id}/edit', [BrandsController::class, 'edit'])->name('edit');
        Route::put('{id}', [BrandsController::class, 'update'])->name('update');
        Route::delete('{id}', [BrandsController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('categories')->name('categories.')->group(function (): void {
        Route::get('/', [CategoriesController::class, 'index'])->name('index');
        Route::get('create', [CategoriesController::class, 'create'])->name('create');
        Route::post('/', [CategoriesController::class, 'store'])->name('store');
        Route::get('{id}/edit', [CategoriesController::class, 'edit'])->name('edit');
        Route::put('{id}', [CategoriesController::class, 'update'])->name('update');
        Route::delete('{id}', [CategoriesController::class, 'destroy'])->name('destroy');
        Route::patch('{id}/reorder', [CategoriesController::class, 'reorder'])->name('reorder');
    });

    Route::prefix('attributes')->name('attributes.')->group(function (): void {
        Route::get('/', [AttributesController::class, 'index'])->name('index');
        Route::get('create', [AttributesController::class, 'create'])->name('create');
        Route::post('/', [AttributesController::class, 'store'])->name('store');
        Route::get('{id}/edit', [AttributesController::class, 'edit'])->name('edit');
        Route::put('{id}', [AttributesController::class, 'update'])->name('update');
        Route::delete('{id}', [AttributesController::class, 'destroy'])->name('destroy');
    });
});
