<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryMovementType;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use App\Support\Admin\AdminTable;
use App\Support\Admin\Tables\InventoryTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Batch 3.3 - the inventory LIST (index, inherited from AdminController)
 * plus the two things that actually write to a variant from this screen:
 * a manual stock movement (adjust()) and low_stock_threshold (update()).
 * Neither goes through create/store/destroy - there is no "creating" a
 * variant here (out of scope: 3.2's product/variant screens own that),
 * only ever an existing one.
 *
 * viewPath()/routeName() are overridden because the base class would
 * otherwise derive "product-variants" from the model name
 * (Str::kebab(Str::plural(class_basename(ProductVariant::class)))) -
 * config/admin-menu.php's existing 'inventory' sidebar entry already
 * points at admin.inventory.index (seeded well before this batch), so
 * that name is a fixed target, not a free choice.
 */
class InventoryController extends AdminController
{
    protected function newModel(): Model
    {
        return new ProductVariant;
    }

    protected function newTable(Request $request): AdminTable
    {
        return new InventoryTable($request);
    }

    protected function viewPath(): string
    {
        return 'admin.inventory.';
    }

    protected function routeName(string $action): string
    {
        return 'admin.inventory.'.$action;
    }

    /**
     * Overridden (not just inherited) to pass the low-stock/out-of-stock
     * counters (Task 6) to the view alongside the table - AdminController::
     * index() has no hook for extra view data. Both counts are plain,
     * uncached COUNT queries run fresh on every request (CLAUDE.md: no
     * caching on stock_quantity or anything derived from it).
     */
    public function index(Request $request): JsonResponse|StreamedResponse|View
    {
        $this->authorize('viewAny', ProductVariant::class);

        $table = $this->newTable($request);

        if ($table->wantsJson() || $request->boolean('export')) {
            return $table->response();
        }

        return view('admin.inventory.index', [
            'table' => $table,
            'lowCount' => ProductVariant::query()
                ->whereRaw('(stock_quantity - reserved_quantity) > 0')
                ->whereRaw('(stock_quantity - reserved_quantity) <= low_stock_threshold')
                ->count(),
            'outCount' => ProductVariant::query()
                ->whereRaw('(stock_quantity - reserved_quantity) <= 0')
                ->count(),
        ]);
    }

    private function findVariant(int|string $id): ProductVariant
    {
        return ProductVariant::query()->with(['product.translations'])->findOrFail($id);
    }

    public function editThreshold(int|string $id): View
    {
        $variant = $this->findVariant($id);
        $this->authorize('update', $variant);

        return view('admin.inventory.threshold', ['variant' => $variant]);
    }

    /**
     * low_stock_threshold is NOT an inventory movement (CLAUDE.md/this
     * batch's own decision: "✅ يتعدّل — 🚫 مش عبر InventoryService") - a
     * plain fillable-column update, no InventoryMovement row, no
     * quantity_before/after.
     */
    public function updateThreshold(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $variant = $this->findVariant($id);
        $this->authorize('update', $variant);

        $data = $request->validate([
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
        ]);

        $variant->update($data);

        return $this->respond($request, redirect()->route('admin.inventory.index'), __('admin.crud.updated'));
    }

    public function createAdjustment(int|string $id): View
    {
        $variant = $this->findVariant($id);
        $this->authorize('update', $variant);

        return view('admin.inventory.adjust', ['variant' => $variant]);
    }

    /**
     * The manual-adjustment screen (Batch 3.3 decision 2's own table):
     * `in`/`out` take a raw quantity (always positive on the wire, sign
     * applied here before reaching InventoryService::adjust()); `adjust`
     * is a stocktake - the admin's true final count, not a delta (see
     * InventoryService::stocktake()'s own docblock for why the delta is
     * computed there, under the lock, never here).
     *
     * note is required for all three types (mandatory audit trail, per
     * this batch's own requirement) - 🚫 never optional, regardless of type.
     */
    public function adjust(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $variant = $this->findVariant($id);
        $this->authorize('update', $variant);

        $data = $request->validate([
            'type' => ['required', Rule::in(['in', 'out', 'adjust'])],
            'quantity' => ['required_if:type,in,out', 'nullable', 'integer', 'min:1'],
            'new_count' => ['required_if:type,adjust', 'nullable', 'integer', 'min:0'],
            'note' => ['required', 'string'],
        ]);

        /** @var \App\Models\Admin|null $admin */
        $admin = Auth::guard('admin')->user();
        $service = app(InventoryService::class);

        match ($data['type']) {
            'in' => $service->adjust($variant, $data['quantity'], InventoryMovementType::In, null, $data['note'], $admin),
            'out' => $service->adjust($variant, -$data['quantity'], InventoryMovementType::Out, null, $data['note'], $admin),
            'adjust' => $service->stocktake($variant, $data['new_count'], $data['note'], $admin),
        };

        return $this->respond($request, redirect()->route('admin.inventory.index'), __('admin.crud.updated'));
    }
}
