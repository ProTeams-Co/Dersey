<?php

namespace App\Models;

use App\Enums\AdminStatus;
use App\Models\Concerns\HasDefaultActivityLog;
use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Its own Authenticatable model on the "admin" guard (config/auth.php) —
 * entirely separate from the storefront User model/guard, per this batch's
 * "guard منفصل" requirement.
 */
class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasDefaultActivityLog, HasFactory, HasRoles, SoftDeletes;

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
}
