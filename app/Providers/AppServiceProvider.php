<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\CauserResolver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // spatie/laravel-activitylog's automatic per-model logging
        // (HasDefaultActivityLog) resolves its causer via the *default*
        // guard only (config('activitylog.default_auth_driver') is null,
        // i.e. "web") - on an admin-panel request the actual actor is on
        // the completely separate `admin` guard, so without this override
        // every automatically-logged admin action would be attributed to
        // no one (Batch 3.0). Admin checked first since an admin acting
        // inside the admin panel is the common case there; falls back to
        // the storefront `web` guard for everything else.
        app(CauserResolver::class)->resolveUsing(
            fn () => Auth::guard('admin')->user() ?? Auth::guard('web')->user()
        );
    }
}
