<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `value` is deliberately a plain unsignedInteger, not MoneyCast: its
     * meaning depends on `type` - piasters when type=fixed, but a raw
     * 0-100 percentage when type=percent (not a money amount at all), and
     * irrelevant when type=free_shipping. Casting it as money would be
     * wrong 2 times out of 3. CouponService is the only place that
     * interprets it correctly per-type. min_order_amount/
     * max_discount_amount ARE always money regardless of type, so those
     * do get MoneyCast.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type'); // DiscountType
            $table->unsignedInteger('value');
            $table->unsignedBigInteger('min_order_amount')->nullable(); // piasters
            $table->unsignedBigInteger('max_discount_amount')->nullable(); // piasters
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('first_order_only')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
