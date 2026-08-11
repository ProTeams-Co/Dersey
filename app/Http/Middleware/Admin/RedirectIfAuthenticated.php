<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the login/forgot-password/reset-password routes from an already
 * logged-in admin - same reasoning as Authenticate: a dedicated class
 * instead of the generic `guest` alias, since that one's default fallback
 * ("/", no "dashboard"/"home" route exists globally) is wrong for the
 * admin panel specifically.
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
