<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A dedicated guard-specific middleware rather than the generic `auth`
 * alias with a global Authenticate::redirectUsing() override - the
 * storefront doesn't have any login-protected routes yet, so hijacking the
 * shared static callback now would silently misdirect whatever storefront
 * auth gets built later. This class only ever knows about the admin guard.
 */
class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                abort(401);
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
