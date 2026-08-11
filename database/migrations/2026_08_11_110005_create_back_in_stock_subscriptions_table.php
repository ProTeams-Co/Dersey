<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Schema not specified in the batch ("الجدول بس") - my own design,
     * flagged as a decision: email (not just user_id) so a guest can
     * subscribe without an account; user_id nullable to link it to one
     * when they're logged in; notified_at left for a future notification
     * batch to stamp, no logic reads/writes it yet. UNIQUE(variant_id,
     * email) stops the same address subscribing to the same variant twice.
     */
    public function up(): void
    {
        Schema::create('back_in_stock_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['variant_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('back_in_stock_subscriptions');
    }
};
