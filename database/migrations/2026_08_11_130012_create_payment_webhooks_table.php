<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * UNIQUE(transaction_id, event_type) is the real idempotency guard,
     * not application code - Paymob is known to redeliver the same
     * webhook more than once, and a second insert attempt for the same
     * (transaction_id, event_type) pair fails at the database level
     * regardless of what the handling code does or doesn't check first.
     */
    public function up(): void
    {
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('transaction_id');
            $table->json('payload');
            $table->boolean('hmac_valid');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->unique(['transaction_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');
    }
};
