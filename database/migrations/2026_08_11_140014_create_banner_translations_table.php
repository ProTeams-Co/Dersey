<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `alt` is NOT NULL on purpose - a banner without alt text hurts SEO
     * and accessibility (batch spec, explicit requirement), so there's no
     * nullable escape hatch for it here at the schema level.
     */
    public function up(): void
    {
        Schema::create('banner_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->constrained()->cascadeOnDelete();
            $table->string('locale');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('button_text')->nullable();
            $table->string('alt');
            $table->timestamps();

            $table->unique(['banner_id', 'locale']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_translations');
    }
};
