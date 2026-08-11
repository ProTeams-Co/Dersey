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
        Schema::create('product_attribute_value', function (Blueprint $table) {
            // For non-variant attributes (is_variant = false - material,
            // season, ...) only, by convention; variant-generating
            // attributes (size, color) are consumed by product_variants in
            // Batch 2.3 instead. Not enforced by a DB constraint here - no
            // business logic in this batch.
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();

            $table->unique(['product_id', 'attribute_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute_value');
    }
};
