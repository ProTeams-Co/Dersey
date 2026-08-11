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
        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale');
            $table->string('name');
            $table->string('slug');
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('material')->nullable();
            $table->text('care_instructions')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'locale']);
            $table->unique(['slug', 'locale']);
            $table->index('locale');

            // Confirmed working against real Arabic text on this exact
            // MariaDB instance (10.4.32/InnoDB) before adding this - a
            // MATCH AGAINST('فستان') query correctly matched and correctly
            // excluded non-matching rows. innodb_ft_min_token_size=3 means
            // Arabic words under 3 letters will not be individually
            // indexed, a minor recall caveat, not a functional blocker.
            //
            // Skipped on sqlite only: the test suite's DB_CONNECTION
            // (phpunit.xml) is sqlite in-memory, and Laravel's schema
            // grammar for sqlite has no fullText() support at all (throws
            // RuntimeException, confirmed by actually running the suite) -
            // this is a testing-driver limitation, not the earlier
            // MySQL-Arabic-support question that was already verified.
            // Production/MySQL always gets the real index.
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['name', 'description']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_translations');
    }
};
