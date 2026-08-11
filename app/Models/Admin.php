<?php

namespace App\Models;

use App\Enums\AdminStatus;
use App\Models\Concerns\HasDefaultActivityLog;
use App\Notifications\AdminResetPasswordNotification;
use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Traits\HasRoles;

/**
 * Its own Authenticatable model on the "admin" guard (config/auth.php) —
 * entirely separate from the storefront User model/guard, per this batch's
 * "guard منفصل" requirement.
 */
class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasDefaultActivityLog, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected string $guard_name = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => AdminStatus::class,
        ];
    }

    /**
     * Overrides CanResetPassword's default (which points at the storefront
     * `password.reset` route) - the admin guard has its own broker
     * (config/auth.php `passwords.admins`) and its own route name.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $url = URL::route('admin.password.reset', ['token' => $token, 'email' => $this->getEmailForPasswordReset()]);

        $this->notify(new AdminResetPasswordNotification($url));
    }
}
