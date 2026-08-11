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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->unsignedBigInteger('price')->nullable();
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->unsignedBigInteger('cost_price')->nullable();

            // Signed on purpose, not unsignedInteger: a bug that pushes
            // this below zero must surface as a negative number (visibly
            // wrong, easy to spot in a query/dashboard), not silently
            // wrap around to a huge unsigned value that looks like a
            // massive, plausible-looking stock count.
            $table->integer('stock_quantity')->default(0);

            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->unsignedInteger('reserved_quantity')->default(0);

            // No FK yet - product_images doesn't exist until the next
            // migration. The constraint itself is added afterward in
            // 2026_08_11_110003_add_image_foreign_to_product_variants_table.
            $table->unsignedBigInteger('image_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);

            // Optimistic locking (App\Support\Traits\HasOptimisticLock) -
            // every stock-affecting write goes through a conditional
            // UPDATE ... WHERE version = :version, so a lost update from
            // two concurrent requests raises StaleModelException instead
            // of silently overwriting one of them.
            $table->unsignedBigInteger('version')->default(0);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index('stock_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
