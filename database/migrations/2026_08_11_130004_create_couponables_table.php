<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restricts a coupon to specific categories/products - a coupon with
     * no rows here applies store-wide (unrestricted); a coupon with rows
     * here applies only to matching couponable_type/couponable_id.
     */
    public function up(): void
    {
        Schema::create('couponables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->morphs('couponable');

            $table->unique(['coupon_id', 'couponable_type', 'couponable_id'], 'couponables_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couponables');
    }
};
