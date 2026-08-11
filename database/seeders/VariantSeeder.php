<?php

namespace Database\Seeders;

use App\Enums\InventoryMovementType;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Inventory\InventoryService;
use Illuminate\Database\Seeder;

/**
 * Every existing product gets variants ("Variants إجبارية" - the
 * mandatory-variants decision from the project plan) - either size only,
 * or size + a curated few colors, alternating per product. material stays
 * a filter-only attribute (is_variant = false, decided in Batch 2.2 and
 * reaffirmed rather than overridden during this batch) - it's already
 * attached to every product via product_attribute_value by ProductSeeder,
 * not part of variant generation here.
 *
 * Size+color products deliberately don't use
 * Product::generateVariants([size, color]) directly: that would take the
 * *entire* color attribute (all 10 seeded values), giving every such
 * product 5 sizes × 10 colors = 50 variants - unrealistic for a single
 * product, and slow to seed 36 times over. A random 2-4 colors per
 * product (still written through the same ProductVariant::
 * syncOptionValues() validation generateVariants() itself uses) keeps the
 * catalog realistic; generateVariants() itself is still exercised as
 * specified, both for the size-only products here and in its own
 * dedicated Pest test.
 *
 * Initial stock is written through InventoryService::adjust() rather than
 * setting stock_quantity directly, so every seeded variant also gets a
 * real opening InventoryMovement row ("حركات مخزون تاريخية" from the
 * batch spec) instead of starting from an unexplained number.
 */
class VariantSeeder extends Seeder
{
    public function run(): void
    {
        $sizeLetterAttributeId = Attribute::where('code', 'size_letter')->value('id');
        $colorAttributeId = Attribute::where('code', 'color')->value('id');

        $sizeValueIds = AttributeValue::where('attribute_id', $sizeLetterAttributeId)->orderBy('sort')->pluck('id');
        $colorValueIds = AttributeValue::where('attribute_id', $colorAttributeId)->pluck('id');

        $inventoryService = app(InventoryService::class);

        $products = Product::orderBy('id')->get();

        foreach ($products as $index => $product) {
            $variants = $index % 3 === 0
                ? $product->generateVariants([$sizeLetterAttributeId])
                : $this->generateSizeColorVariants($product, $sizeValueIds, $colorValueIds);

            foreach ($variants as $variantIndex => $variant) {
                $stockState = ($index + $variantIndex) % 5;

                $initialStock = match (true) {
                    $stockState === 0 => 0, // out of stock
                    $stockState === 1 => $variant->low_stock_threshold, // low stock
                    default => fake()->numberBetween(10, 80), // healthy stock
                };

                if ($initialStock > 0) {
                    $inventoryService->adjust(
                        $variant,
                        $initialStock,
                        InventoryMovementType::In,
                        note: 'Initial seed stock.'
                    );
                }
            }

            $this->seedImages($product, $colorAttributeId);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $sizeValueIds
     * @param  \Illuminate\Support\Collection<int, int>  $colorValueIds
     * @return \Illuminate\Support\Collection<int, \App\Models\ProductVariant>
     */
    private function generateSizeColorVariants(Product $product, $sizeValueIds, $colorValueIds)
    {
        $chosenColorIds = $colorValueIds->random(fake()->numberBetween(2, 4));
        $variants = collect();

        foreach ($sizeValueIds as $sizeValueId) {
            foreach ($chosenColorIds as $colorValueId) {
                // low_stock_threshold set explicitly - see the matching
                // comment in Product::generateVariants(); this seeder is
                // exactly what surfaced the bug (read as null, treated
                // "low stock" variants as needing 0 initial stock).
                $variant = $product->variants()->create([
                    'sku' => "{$product->sku}-".($variants->count() + 1),
                    'stock_quantity' => 0,
                    'low_stock_threshold' => 5,
                ]);

                $variant->syncOptionValues([$sizeValueId, $colorValueId]);
                $variants->push($variant);
            }
        }

        return $variants;
    }

    private function seedImages(Product $product, int $colorAttributeId): void
    {
        $colorValueIds = $product->variants()
            ->with('attributeValues')
            ->get()
            ->flatMap(fn ($variant) => $variant->attributeValues->where('attribute_id', $colorAttributeId))
            ->pluck('id')
            ->unique()
            ->values();

        $imageCount = fake()->numberBetween(3, 5);

        for ($i = 0; $i < $imageCount; $i++) {
            ProductImage::factory()
                ->when($i === 0, fn ($factory) => $factory->primary())
                ->create([
                    'product_id' => $product->id,
                    'color_value_id' => $colorValueIds->isNotEmpty()
                        ? $colorValueIds->get($i % $colorValueIds->count())
                        : null,
                    'sort' => $i,
                ]);
        }
    }
}
