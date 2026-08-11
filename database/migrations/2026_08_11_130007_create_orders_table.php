<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * No SoftDeletes - orders are never deleted, per this batch's rule
     * (cancellation is a status, OrderStatus::Cancelled). coupon_id is
     * nullOnDelete and coupon_code is a separate snapshot column: even if
     * the Coupon row is later deleted, coupon_code preserves what was
     * actually applied on this order.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('status'); // OrderStatus
            $table->string('payment_status'); // PaymentStatus
            $table->unsignedBigInteger('subtotal'); // piasters
            $table->unsignedBigInteger('discount_total'); // piasters
            $table->unsignedBigInteger('shipping_total'); // piasters
            $table->unsignedBigInteger('tax_total'); // piasters
            $table->unsignedBigInteger('grand_total'); // piasters
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable(); // snapshot - survives coupon deletion
            $table->string('currency')->default('EGP');
            $table->string('payment_method'); // PaymentMethod
            $table->json('shipping_address'); // full address snapshot
            $table->json('billing_address')->nullable();
            $table->json('shipping_method_name'); // {"ar": "...", "en": "..."} snapshot
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('locale');
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('placed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
