<?php

use App\Http\Middleware\Admin\Authenticate as AdminAuthenticate;
use App\Http\Middleware\Admin\EnsureAdminIsActive;
use App\Http\Middleware\Admin\RedirectIfAuthenticated as RedirectIfAdminAuthenticated;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(__DIR__.'/../routes/admin.php');

            Route::middleware('web')
                ->prefix('ajax')
                ->name('ajax.')
                ->group(__DIR__.'/../routes/ajax.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestId::class);

        $middleware->alias([
            'localizationRedirect' => LaravelLocalizationRedirectFilter::class,
            'localizationViewPath' => LaravelLocalizationViewPath::class,
            'setLocale' => SetLocale::class,
            'admin.auth' => AdminAuthenticate::class,
            'admin.guest' => RedirectIfAdminAuthenticated::class,
            'admin.active' => EnsureAdminIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
