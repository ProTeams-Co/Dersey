<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `remember_token` was already referenced by Admin::$hidden but the column
 * never existed (dead reference until now) - added here since "remember
 * me" is a normal part of a login flow (Batch 3.0). `last_login_ip` is the
 * batch's own explicit requirement, alongside the already-existing
 * `last_login_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->rememberToken()->after('password');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['remember_token', 'last_login_ip']);
        });
    }
};
