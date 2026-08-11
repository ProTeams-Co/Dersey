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
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type'); // AttributeType
            $table->boolean('is_filterable')->default(false);
            // true (size, color): generates variants in Batch 2.3.
            // false (material, season): filtering only, never a variant axis.
            $table->boolean('is_variant')->default(false);
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            // Not listed explicitly in this table's own field list, but
            // "soft delete على الكتالوج كله" (this batch's blanket rule)
            // covers attributes as catalog metadata the same as categories/
            // brands/products.
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
