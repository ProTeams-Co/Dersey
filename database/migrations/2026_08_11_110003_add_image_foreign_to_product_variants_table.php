<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Separate migration because product_variants.image_id (created in
     * 2026_08_11_110000) has to exist before product_images does (its
     * target table), so the FK constraint can only be added once
     * product_images exists - same pattern as categories.parent_id in
     * Batch 2.2, just across two tables instead of one self-reference.
     *
     * nullOnDelete: a variant losing its specific image (image row
     * deleted) should fall back to Variant::displayImage()'s other
     * sources, not be blocked from ever being deleted itself.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreign('image_id')->references('id')->on('product_images')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
        });
    }
};
