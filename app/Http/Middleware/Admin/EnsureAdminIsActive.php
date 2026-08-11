<?php

namespace App\Http\Middleware\Admin;

use App\Enums\AdminStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after Authenticate (admin.auth) - a suspended admin can still have a
 * live session (suspension doesn't force-logout an already-open browser
 * tab on its own), so this must be checked on every request, not just at
 * login time.
 */
class EnsureAdminIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && $admin->status !== AdminStatus::Active) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, __('admin.auth.suspended'));
            }

            return redirect()->route('admin.login')->withErrors(['email' => __('admin.auth.suspended')]);
        }

        return $next($request);
    }
}
