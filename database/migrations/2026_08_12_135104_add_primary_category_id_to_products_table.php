<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A dedicated column, not a flag on the category_product pivot -
     * category_product stays a plain sync()-able pivot (every category a
     * product belongs to, primary included); this column alone answers
     * "which one is primary" without a MySQL partial-unique-index trick
     * (not supported) and without sync() silently reshuffling which row
     * is "primary" on every categories-tab save. nullOnDelete (not
     * restrict/cascade): deleting the primary category unsets this
     * instead of blocking the category's own deletion or cascading the
     * product away - the product then simply fails the publish gate's
     * "has a primary category" condition again until an admin picks a
     * new one.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // No separate index('primary_category_id') - constrained()
            // above already creates one (InnoDB requires an index on the
            // FK column), matching this project's existing convention of
            // not duplicating FK-covered indexes.
            $table->foreignId('primary_category_id')->nullable()->after('brand_id')->constrained('categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['primary_category_id']);
            $table->dropColumn('primary_category_id');
        });
    }
};
