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
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('color_hex')->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->softDeletes();
            $table->timestamps();

            // No separate index('attribute_id') - the FK constraint above
            // already provides one (InnoDB requires it), matching the
            // Batch 2.1 precedent of not duplicating FK-covered indexes.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
