<?php

namespace App\Console\Commands;

use App\Enums\AdminStatus;
use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

/**
 * The only way to create an admin account outside RolePermissionSeeder's
 * env-driven super-admin bootstrap - there is no self-registration and no
 * "create admin" screen in the panel yet (AdminsController is index-only,
 * Batch 3.0). Options let it run non-interactively (CI, deploy scripts);
 * omitting them prompts instead.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
        {--name= : اسم الأدمن}
        {--email= : البريد الإلكتروني}
        {--password= : كلمة المرور}
        {--role= : اسم الدور (super-admin, admin, manager, support)}';

    protected $description = 'إنشاء حساب أدمن جديد في لوحة التحكم';

    public function handle(): int
    {
        $roles = Role::where('guard_name', 'admin')->pluck('name')->all();

        if ($roles === []) {
            $this->error('مفيش أدوار متاحة على guard "admin" - شغّل RolePermissionSeeder الأول.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?? $this->ask('الاسم');
        $email = $this->option('email') ?? $this->ask('البريد الإلكتروني');
        $password = $this->option('password') ?? $this->secret('كلمة المرور');
        $role = $this->option('role') ?? $this->choice('الدور', $roles, 0);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:admins,email'],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['required', 'string', 'in:'.implode(',', $roles)],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $admin = Admin::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'status' => AdminStatus::Active,
        ]);

        $admin->assignRole($role);

        $this->info("تم إنشاء الحساب بنجاح: {$admin->email} (الدور: {$role})");

        return self::SUCCESS;
    }
}
