<?php

namespace App\Http\Controllers\Admin\Auth\Concerns;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Laravel core no longer ships a ThrottlesLogins trait (it moved to the
 * now-unused laravel/ui scaffolding) - this reimplements the same
 * well-known RateLimiter-based pattern: 5 failed attempts per (email, ip)
 * pair, each hit decaying after 60 seconds, so the lockout is temporary
 * rather than permanent.
 */
trait ThrottlesLogins
{
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('admin.auth.throttle', ['seconds' => $seconds]),
        ]);
    }

    protected function incrementLoginAttempts(Request $request): void
    {
        RateLimiter::hit($this->throttleKey($request), 60);
    }

    protected function clearLoginAttempts(Request $request): void
    {
        RateLimiter::clear($this->throttleKey($request));
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());
    }
}
