<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_category_id')->constrained()->cascadeOnDelete();
            $table->string('locale');
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['post_category_id', 'locale']);
            $table->unique(['slug', 'locale']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_category_translations');
    }
};
