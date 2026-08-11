<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // unique + nullable, not unique alone — MySQL/MariaDB allow any
            // number of NULLs in a unique index (only non-null duplicates
            // are rejected), so guest/social-only accounts without a phone
            // yet are never blocked from being created.
            $table->string('phone')->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('gender')->nullable()->after('password');
            $table->date('birth_date')->nullable()->after('gender');
            $table->string('locale')->nullable()->after('birth_date');
            $table->string('status')->default('active')->after('locale');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'phone_verified_at', 'gender', 'birth_date', 'locale',
                'status', 'last_login_at', 'last_login_ip', 'deleted_at',
            ]);
        });
    }
};
