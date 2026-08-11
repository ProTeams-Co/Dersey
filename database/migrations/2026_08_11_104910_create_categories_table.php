<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * No `depth` column - kalnoy/nestedset v6 (the version installed here)
     * does not maintain one; it only computes depth on demand via
     * ->withDepth(), verified directly against the installed package
     * source rather than assumed. _lft/_rgt already fully encode the
     * hierarchy, so a hand-maintained depth column would just be a second,
     * driftable source of truth for something already derivable.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_in_menu')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // $table->nestedSet() is NOT used here: it creates parent_id as
            // unsignedInteger (32-bit), but categories.id is
            // unsignedBigInteger (64-bit, via id() above) - MySQL refuses
            // a foreign key between mismatched column widths (errno 150,
            // confirmed by actually running this migration). _lft/_rgt/
            // parent_id are recreated by hand instead, matching
            // NestedSet::columns() exactly except parent_id's width.
            $table->unsignedInteger('_lft')->default(0);
            $table->unsignedInteger('_rgt')->default(0);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->index(['parent_id', '_lft', '_rgt']);
        });

        // nullOnDelete would be wrong here - it would let a parent be
        // deleted out from under live children. restrict backs up
        // CategoryObserver's own "no deleting a category with children"
        // check at the database level.
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('categories')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
