<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        // Same response whether or not the email matches a real admin -
        // revealing which is a user-enumeration side channel.
        Password::broker('admins')->sendResetLink(
            $request->only('email')
        );

        return back()->with('status', __('admin.auth.reset_link_sent'));
    }
}
