<?php

use App\Http\Middleware\AssignRequestId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
