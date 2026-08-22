<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Order\OrderService;
use App\Support\Admin\AdminTable;
use App\Support\Admin\Tables\OrdersTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Batch 3.4 - index (list) is the only AdminController base action this
 * screen actually uses. create()/store()/destroy()/bulkAction() are
 * inherited but never routed (see routes/admin.php's own comment) - there
 * is no "creating" an order from this screen, no deleting one (CLAUDE.md:
 * financial records are never deleted), and a bulk status change across
 * orders is out of scope by this batch's own explicit rule (one wrong
 * status jump touches inventory AND money at once).
 *
 * show() replaces AdminController::edit() entirely - this is a read-heavy
 * page (snapshot items, status history, payments, shipments, inventory
 * movements) with a handful of separate, narrow write actions
 * (admin_note, a status transition, shipments) rather than one big form
 * PUT, so the base edit()/update() pair doesn't fit and isn't used.
 */
class OrdersController extends AdminController
{
    protected function newModel(): Model
    {
        return new Order;
    }

    protected function newTable(Request $request): AdminTable
    {
        return new OrdersTable($request);
    }

    public function show(int|string $id): View
    {
        $order = $this->findOrderOrFail($id);
        $this->authorize('view', $order);

        return view('admin.orders.show', [
            'order' => $order,
            'availableTransitions' => $this->allowedTransitionsFor($order),
        ]);
    }

    /**
     * Every relation the show view touches, eager-loaded once - including
     * statusHistories.changedBy, a polymorphic morphTo(): Eloquent groups
     * eager-loaded morphTo() targets by their actual type (one query per
     * distinct changed_by_type present - Admin, User, or none), not one
     * query per row, so this stays N+1-free regardless of how many
     * different admins/users appear across a single order's history.
     */
    private function findOrderOrFail(int|string $id): Order
    {
        return Order::query()
            ->with([
                'user',
                'items',
                'statusHistories' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
                'statusHistories.changedBy',
                'shipments',
                'payments',
                'inventoryMovements' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
                'inventoryMovements.variant',
                'inventoryMovements.admin',
            ])
            ->findOrFail($id);
    }

    /**
     * The ONLY editable field on an order (CLAUDE.md/this batch's own
     * rule) - order_items is an immutable snapshot, payment_status is
     * Paymob's to control (Phase 5), and status only ever moves through
     * transition() below, never a plain fill()->save().
     */
    public function updateNote(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $order = $this->findOrderOrFail($id);
        $this->authorize('update', $order);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string'],
        ]);

        $order->update($data);

        return $this->respond($request, redirect()->route('admin.orders.show', $order->id), __('admin.crud.updated'));
    }

    /**
     * The only path that ever changes order.status - OrderService::
     * transitionTo() itself enforces OrderStatus::canTransitionTo() (an
     * illegal jump throws InvalidOrderTransitionException, rendered as a
     * 422, never a 500) and writes the order_status_histories row. The
     * comment is required at the HTTP boundary too (not just relying on
     * the service - the service's own $comment param is nullable, since
     * OrderSeeder's system-initiated transitions legitimately pass one
     * without an admin behind it), matching this batch's own "التعليق
     * إلزامي" rule for an ADMIN-initiated change specifically.
     */
    public function transition(Request $request, int|string $id, OrderService $orderService): JsonResponse|RedirectResponse
    {
        $order = $this->findOrderOrFail($id);
        $this->authorize('update', $order);

        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'comment' => ['required', 'string'],
        ]);

        $orderService->transitionTo(
            $order,
            OrderStatus::from($data['status']),
            $data['comment'],
            Auth::guard('admin')->user(),
        );

        return $this->respond($request, redirect()->route('admin.orders.show', $order->id), __('admin.crud.updated'));
    }

    /**
     * OrderStatus::allowedTransitions() is private (by design - the enum
     * is the single source of truth for the state machine, not something
     * a caller reads directly, see CLAUDE.md). canTransitionTo() (public)
     * checked against every case reconstructs the same list without
     * touching the enum at all - a final status naturally yields an empty
     * array, since nothing satisfies canTransitionTo() from there.
     *
     * @return list<OrderStatus>
     */
    private function allowedTransitionsFor(Order $order): array
    {
        return array_values(array_filter(
            OrderStatus::cases(),
            fn (OrderStatus $case) => $order->status->canTransitionTo($case)
        ));
    }
}
