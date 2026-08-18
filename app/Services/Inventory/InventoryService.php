<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Events\VariantLowStock;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\StocktakeNoChangeException;
use App\Models\Admin;
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
    /**
     * $admin is optional and last on purpose (Batch 3.3 decision 1) - every
     * one of adjust()'s 2 existing callers before this batch (order
     * cancellation restocking, ProductVariantMatrixService's initial-stock
     * write) is itself an automatic/system-initiated write with no admin
     * actually behind it, and stays that way by default (null admin_id) -
     * only the new manual-adjustment admin screen (Batch 3.3) passes one
     * explicitly. null here is NOT "unknown admin" - it's the correct,
     * meaningful value for a movement nothing human actually triggered.
     */
    public function adjust(
        ProductVariant $variant,
        int $quantity,
        InventoryMovementType $type,
        ?Model $reference = null,
        ?string $note = null,
        ?Admin $admin = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($variant, $quantity, $type, $reference, $note, $admin) {
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
                'admin_id' => $admin?->id,
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
     *
     * $reference is optional (added in Batch 2.4, backward-compatible with
     * existing callers) so the resulting movement can link back to the
     * Order it belongs to, same as adjust() already supports.
     *
     * $admin optional, same reasoning as adjust() above - commit()'s only
     * caller (OrderService::createFromCart(), during checkout) never has
     * an admin behind it, so this stays null there by default; nothing
     * currently passes one, but the parameter exists for the same
     * consistency reason (a future admin-initiated commit shouldn't need
     * a signature change to attribute itself).
     */
    public function commit(ProductVariant $variant, int $quantity, ?Model $reference = null, ?Admin $admin = null): InventoryMovement
    {
        return DB::transaction(function () use ($variant, $quantity, $reference, $admin) {
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
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'admin_id' => $admin?->id,
            ]);

            $this->dispatchLowStockIfNeeded($locked);

            return $movement;
        });
    }

    /**
     * Batch 3.3 decision 2 - InventoryMovementType::Adjust's first real
     * consumer. Fundamentally different shape from adjust(): the admin
     * enters the TRUE FINAL COUNT from a physical stocktake, not a delta -
     * "the shelf has 47 units", not "add 5". The delta (which can land
     * either positive or negative) is computed HERE, after the row lock is
     * acquired, never by the caller beforehand - a delta precomputed from
     * an unlocked read could be stale by the time this actually writes,
     * silently landing on the wrong final count under concurrent access
     * (the exact class of bug every other lockForUpdate()-first method in
     * this service exists to prevent).
     *
     * $newCount is trusted to already be >= 0 (the admin's own request
     * validation layer rejects a negative count as a normal 422 before
     * this is ever called - never left to InsufficientStockException's
     * "insufficient stock" wording, which would be a confusing, wrong
     * message for a stocktake miscount).
     */
    public function stocktake(ProductVariant $variant, int $newCount, string $note, ?Admin $admin = null): InventoryMovement
    {
        return DB::transaction(function () use ($variant, $newCount, $note, $admin) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

            $before = $locked->stock_quantity;
            $delta = $newCount - $before;

            if ($delta === 0) {
                throw new StocktakeNoChangeException($locked);
            }

            $locked->stock_quantity = $newCount;
            $locked->saveWithVersion();

            $movement = InventoryMovement::create([
                'variant_id' => $locked->id,
                'type' => InventoryMovementType::Adjust,
                'quantity' => $delta,
                'quantity_before' => $before,
                'quantity_after' => $newCount,
                'admin_id' => $admin?->id,
                'note' => $note,
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
