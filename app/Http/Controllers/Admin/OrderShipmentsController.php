<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Support\Money;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Batch 3.4 decision (Task 4) - a shipment only exists attached to an
 * order, so this is its own small controller rather than folded into
 * OrdersController, same "one controller per distinct shape of action"
 * reasoning ProductVariantsController/ProductImagesController already
 * established (Batch 3.2-B/C) - not an AdminController subclass, since
 * there is no list/index for shipments on their own, only create/update
 * scoped to one order.
 *
 * No destroy() at all (Task 4's own rule: shipment deletion is forbidden) -
 * there is no route for it, matching how this project handles "this
 * action must not exist" elsewhere (no delete route for Order itself).
 * Adding a shipment never touches order.status - Task 4's own "العلاقة
 * بالحالة مستقلة" rule - this controller only ever writes to `shipments`.
 */
class OrderShipmentsController extends Controller
{
    use AuthorizesRequests;

    public function authorize($ability, $arguments = [])
    {
        return $this->authorizeForUser(Auth::guard('admin')->user(), $ability, $arguments);
    }

    public function store(Request $request, int|string $orderId): JsonResponse|RedirectResponse
    {
        $order = Order::query()->findOrFail($orderId);
        $this->authorize('create', Shipment::class);

        $order->shipments()->create($this->validated($request));

        return $this->respond($request, __('admin.crud.created'), $order->id);
    }

    public function update(Request $request, int|string $orderId, int|string $shipmentId): JsonResponse|RedirectResponse
    {
        $order = Order::query()->findOrFail($orderId);
        $shipment = $order->shipments()->findOrFail($shipmentId);
        $this->authorize('update', $shipment);

        $shipment->update($this->validated($request));

        return $this->respond($request, __('admin.crud.updated'), $order->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        // Same major-unit-decimal-string convention as every other money
        // input in the admin panel (ProductsController::convertPriceFields(),
        // Batch 3.2-M) - the admin types "45.50" (EGP), never raw piasters;
        // Money::fromMajor() converts before MoneyCast ever sees it. The
        // regex matches Money::fromMajor()'s own pattern exactly, so a bad
        // format is always a 422 from validation here, never a 500 from
        // inside fromMajor().
        $data = $request->validate([
            'carrier' => ['required', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:2048'],
            'cost' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'shipped_at' => ['nullable', 'date'],
        ]);

        $data['cost'] = Money::fromMajor($data['cost']);

        return $data;
    }

    /**
     * Matches AdminController::respond()'s exact {message, redirect}
     * JSON contract (core/form.js's own expectation) without extending
     * that class - this controller has nothing else in common with it
     * (no newModel()/newTable(), no index/create/store/destroy shape).
     */
    private function respond(Request $request, string $message, int|string $orderId): JsonResponse|RedirectResponse
    {
        $redirect = redirect()->route('admin.orders.show', $orderId)->with('status', $message);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => $message, 'redirect' => $redirect->getTargetUrl()]);
        }

        return $redirect;
    }
}
