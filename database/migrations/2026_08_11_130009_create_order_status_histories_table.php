<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * changed_by is polymorphic (Admin, User, or absent entirely for a
     * system-initiated transition e.g. an expired-cart cleanup) -
     * nullableMorphs, not morphs, since "changed by nobody" (an automated
     * process) is a legitimate case.
     */
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable(); // OrderStatus - null on the first row
            $table->string('to_status'); // OrderStatus
            $table->text('comment')->nullable();
            $table->nullableMorphs('changed_by');
            $table->timestamps();

            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
