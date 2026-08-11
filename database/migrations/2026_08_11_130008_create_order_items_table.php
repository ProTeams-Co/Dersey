<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The most important table in this batch: product_id/variant_id are
     * nullable with nullOnDelete on purpose - if the product is later
     * deleted (or hard-deleted, renamed, repriced), this row's own
     * product_name/variant_options/sku/image_path/unit_price/line_total
     * snapshot is completely unaffected, since none of it is a live
     * reference to the catalog. The FK only exists to link back to a
     * *still-existing* product/variant when possible; losing that link
     * loses nothing the order actually needs to display itself correctly.
     *
     * No timestamps - an order item is an immutable snapshot created once
     * alongside its order and never updated afterward; the order's own
     * placed_at already records "when." OrderItem sets $timestamps = false.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->json('product_name'); // {"ar": "...", "en": "..."} snapshot
            $table->json('variant_options')->nullable(); // e.g. {"ar": "M / أسود", "en": "M / Black"} snapshot
            $table->string('sku');
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('unit_price'); // piasters
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total'); // piasters
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
