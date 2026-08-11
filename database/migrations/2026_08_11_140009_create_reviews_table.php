<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * UNIQUE(product_id, user_id, order_item_id) allows multiple NULL
     * order_item_id rows per (product_id, user_id) pair (MySQL treats NULL
     * as distinct in unique indexes) - a review not tied to a specific
     * purchase doesn't block a later verified-purchase review for the
     * same product, or vice versa. This is the literal spec constraint,
     * not an oversight.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->string('title')->nullable();
            $table->text('comment');
            $table->json('images')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->string('status'); // ReviewStatus
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'user_id', 'order_item_id']);
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
