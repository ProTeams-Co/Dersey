<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * No `slug` column here - approved decision: slug lives on
     * page_translations (translated per locale), matching
     * categories/brands/products exactly, not a single column shared
     * across languages. The batch spec listed `slug` under `pages` but
     * also required UNIQUE(slug, locale) on page_translations, which only
     * makes sense if slug is actually there.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('template')->default('default');
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
