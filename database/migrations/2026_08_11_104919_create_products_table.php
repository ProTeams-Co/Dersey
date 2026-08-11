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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // null on delete, not restrict/cascade - a product surviving its
            // brand going away (deleted or unassigned) is normal; it just
            // becomes brand-less, it does not need to block the brand
            // deletion or vanish itself.
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->unique();
            // All three in piasters via MoneyCast, like every other money
            // column in the project - no exception here despite compare_at/
            // cost being nullable.
            $table->unsignedBigInteger('base_price');
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->unsignedBigInteger('cost_price')->nullable();
            $table->string('gender'); // Gender
            $table->string('season')->nullable();
            $table->string('status'); // ProductStatus
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->timestamp('published_at')->nullable();
            // The one deliberate decimal column in the whole project - a
            // rating (0.00-5.00), not money. Do not treat this as a
            // precedent for any monetary column; every amount elsewhere
            // stays an integer piaster count through MoneyCast.
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('weight'); // grams
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('gender');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
