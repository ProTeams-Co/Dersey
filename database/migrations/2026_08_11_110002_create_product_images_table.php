<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SoftDeletes added even though this batch's own field list for
     * product_images didn't spell it out explicitly (unlike
     * product_variants, which does) - same judgment call as Batch 2.2's
     * attributes/attribute_values tables: the batch's own blanket rule
     * ("soft delete على الكتالوج") covers it, and product_images is
     * catalog content like everything else here. Flagged as a decision,
     * not silently assumed.
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // restrict, not cascade: deleting a color that's actually
            // pictured in the gallery must fail loudly, same reasoning as
            // product_variant_values.attribute_value_id.
            $table->foreignId('color_value_id')->nullable()->constrained('attribute_values')->restrictOnDelete();

            $table->string('path');

            // Translated via spatie/laravel-translatable (JSON column),
            // not this project's own separate-table HasTranslations - alt
            // text is neither searched nor indexed, so it belongs in the
            // "reference data" half of the hybrid translation approach
            // (CLAUDE.md), same as Governorate/City names.
            $table->json('alt');

            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_primary')->default(false);

            // Mandatory, not nullable: without real dimensions up front,
            // <img> can't be given width/height/aspect-ratio, and product
            // cards/PDP images would cause layout shift (CLS) while they
            // load - see CLAUDE.md's storage rules.
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');

            $table->string('blurhash')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['product_id', 'sort']);
            $table->index('color_value_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
