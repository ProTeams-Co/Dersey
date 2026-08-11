<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\ResetPasswordRequest;
use App\Models\Admin;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(string $token): View
    {
        return view('admin.auth.reset-password', [
            'token' => $token,
            'email' => request()->query('email'),
        ]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker('admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Admin $admin, string $password) {
                $admin->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($admin));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [$this->translateStatus($status)],
            ]);
        }

        return redirect()->route('admin.login')->with('status', __('admin.auth.password_reset'));
    }

    /**
     * Maps the Password broker's own status constants (its default
     * translation keys, e.g. "passwords.token") to this panel's own
     * admin.auth.* lang keys, since CLAUDE.md requires every admin string
     * to come from lang/admin.php, not the framework's default passwords.php.
     */
    private function translateStatus(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => __('admin.auth.invalid_token'),
            Password::INVALID_USER => __('admin.auth.invalid_user'),
            Password::RESET_THROTTLED => __('admin.auth.reset_throttled'),
            default => __('admin.auth.reset_failed'),
        };
    }
}
