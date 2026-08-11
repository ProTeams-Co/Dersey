<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Events\VariantLowStock;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The only place stock_quantity/reserved_quantity are ever written.
 * Every method: opens a transaction, takes a row-level lock
 * (lockForUpdate) on the variant before reading its current numbers (so a
 * second concurrent call blocks until the first commits, rather than
 * racing off a stale read), writes through
 * ProductVariant::saveWithVersion() (optimistic locking as a second,
 * independent safety net - see HasOptimisticLock), and always logs an
 * InventoryMovement row in the same transaction. Never lets stock go
 * negative or a reservation exceed what's actually available.
 */
class InventoryService
{
    public function adjust(
        ProductVariant $variant,
        int $quantity,
        InventoryMovementType $type,
        ?Model $reference = null,
        ?string $note = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($variant, $quantity, $type, $reference, $note) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

            $before = $locked->stock_quantity;
            $after = $before + $quantity;

            if ($after < 0) {
                throw new InsufficientStockException($locked, abs($quantity));
            }

            $locked->stock_quantity = $after;
            $locked->saveWithVersion();

            $movement = InventoryMovement::create([
                'variant_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'note' => $note,
            ]);

            $this->dispatchLowStockIfNeeded($locked);

            return $movement;
        });
    }

    /**
     * Holds `$quantity` units against an active cart - never lets
     * reserved_quantity exceed stock_quantity (i.e. available_quantity
     * can't go negative), regardless of how much raw stock_quantity there
     * still is.
     */
    public function reserve(ProductVariant $variant, int $quantity): InventoryMovement
    {
        return DB::transaction(function () use ($variant, $quantity) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

            $available = $locked->stock_quantity - $locked->reserved_quantity;

            if ($quantity > $available) {
                throw new InsufficientStockException($locked, $quantity);
            }

            $before = $locked->reserved_quantity;
            $locked->reserved_quantity = $before + $quantity;
            $locked->saveWithVersion();

            return InventoryMovement::create([
                'variant_id' => $locked->id,
                'type' => InventoryMovementType::Reserve,
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $locked->reserved_quantity,
            ]);
        });
    }

    /**
     * Gives back a hold from reserve() - on cart removal or cart/reservation
     * expiry. Clamped at zero rather than allowed to go negative in case
     * of a caller releasing more than is currently held (e.g. a duplicate
     * release), since reserved_quantity going negative would make
     * available_quantity exceed real stock_quantity.
     */
    public function release(ProductVariant $variant, int $quantity): InventoryMovement
    {
        return DB::transaction(function () use ($variant, $quantity) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

            $before = $locked->reserved_quantity;
            $after = max(0, $before - $quantity);

            $locked->reserved_quantity = $after;
            $locked->saveWithVersion();

            return InventoryMovement::create([
                'variant_id' => $locked->id,
                'type' => InventoryMovementType::Release,
                'quantity' => -$quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
            ]);
        });
    }

    /**
     * Converts a reservation into an actual stock decrease on order
     * confirmation: stock_quantity and reserved_quantity both drop by
     * `$quantity`. Logged as InventoryMovementType::Out - the enum has no
     * separate "commit" case, and this genuinely is stock leaving
     * inventory, the same direction as any other Out movement.
     */
    public function commit(ProductVariant $variant, int $quantity): InventoryMovement
    {
        return DB::transaction(function () use ($variant, $quantity) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

            if ($quantity > $locked->stock_quantity) {
                throw new InsufficientStockException($locked, $quantity);
            }

            $stockBefore = $locked->stock_quantity;
            $locked->stock_quantity = $stockBefore - $quantity;
            $locked->reserved_quantity = max(0, $locked->reserved_quantity - $quantity);
            $locked->saveWithVersion();

            $movement = InventoryMovement::create([
                'variant_id' => $locked->id,
                'type' => InventoryMovementType::Out,
                'quantity' => -$quantity,
                'quantity_before' => $stockBefore,
                'quantity_after' => $locked->stock_quantity,
            ]);

            $this->dispatchLowStockIfNeeded($locked);

            return $movement;
        });
    }

    private function dispatchLowStockIfNeeded(ProductVariant $variant): void
    {
        if ($variant->stock_quantity <= $variant->low_stock_threshold) {
            event(new VariantLowStock($variant));
        }
    }
}
