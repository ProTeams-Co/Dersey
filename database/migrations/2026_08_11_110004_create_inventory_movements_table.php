<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * No SoftDeletes, no delete path at all - this is a financial/audit
     * record (CLAUDE.md: "مفيش حذف نهائي للسجلات المالية أبدًا"). variant_id
     * is restrictOnDelete for the same reason: a variant with movement
     * history can never be force-deleted out from under its own audit
     * trail (soft-deleting the variant itself is unaffected, since a plain
     * UPDATE deleted_at never triggers an FK constraint at all).
     */
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('type'); // InventoryMovementType

            // Signed, matching product_variants.stock_quantity: positive
            // for movements that add stock (in), negative for movements
            // that remove it (out, commit), so quantity_before + quantity
            // always equals quantity_after without a per-type sign lookup.
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');

            $table->nullableMorphs('reference');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['variant_id', 'created_at']);
            // reference_type/reference_id index already added by nullableMorphs() above.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
