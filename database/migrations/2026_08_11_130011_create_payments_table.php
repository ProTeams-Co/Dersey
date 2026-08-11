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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway')->default('paymob');
            $table->string('method'); // PaymentMethod
            $table->unsignedBigInteger('amount'); // piasters
            $table->string('status'); // PaymentStatus
            $table->string('paymob_intention_id')->nullable();
            $table->string('paymob_order_id')->nullable();
            $table->string('paymob_transaction_id')->nullable();
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('paymob_intention_id');
            $table->index('paymob_order_id');
            $table->index('paymob_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
