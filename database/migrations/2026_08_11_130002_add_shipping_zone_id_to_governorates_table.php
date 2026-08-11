<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Deferred from Batch 2.1 (governorates existed before shipping_zones
     * did). A direct column, not a governorate_shipping_zone pivot table -
     * approved decision: "one governorate = one zone" is a plain 1:N
     * relationship (many governorates, one zone each), and a pivot table
     * with a UNIQUE(governorate_id) constraint to fake a 1:1/1:N shape
     * would just be a second, driftable source of truth for the same fact.
     * nullable: a governorate can exist before it's been assigned to a
     * zone. nullOnDelete: deleting a zone shouldn't force-delete the
     * governorates in it, just leave them unassigned.
     */
    public function up(): void
    {
        Schema::table('governorates', function (Blueprint $table) {
            $table->foreignId('shipping_zone_id')->nullable()->after('code')
                ->constrained('shipping_zones')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('governorates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_zone_id');
        });
    }
};
