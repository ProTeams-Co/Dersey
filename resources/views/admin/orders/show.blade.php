@extends('layouts.admin')

@section('content')
    @php
        $address = $order->shipping_address;
        $locale = app()->getLocale();
    @endphp

    <x-admin.page
        :title="__('admin.orders.show_title', ['number' => $order->order_number])"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.orders.title'), 'href' => route('admin.orders.index')],
            ['label' => $order->order_number],
        ]"
    >
        @if ($order->status === App\Enums\OrderStatus::Returned)
            <x-ui.alert type="warning" class="mb-4">
                <p>{{ __('admin.orders.returned_stock_notice') }}</p>
            </x-ui.alert>
        @endif

        {{-- Header --}}
        <x-admin.section :title="__('admin.orders.section_header')">
            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-muted">{{ __('admin.orders.column_status') }}</dt>
                    <dd class="mt-1">{!! '<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium '
                        . match($order->status->color()) {
                            'success' => 'bg-success text-success-foreground',
                            'warning' => 'bg-warning text-warning-foreground',
                            'danger' => 'bg-danger text-danger-foreground',
                            'accent' => 'bg-accent text-accent-foreground',
                            default => 'bg-neutral-200 text-ink',
                        } . '">' . e($order->status->label()) . '</span>' !!}</dd>
                </div>
                <div>
                    <dt class="text-muted">{{ __('admin.orders.column_payment_status') }}</dt>
                    <dd class="mt-1">{!! '<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium '
                        . match($order->payment_status->color()) {
                            'success' => 'bg-success text-success-foreground',
                            'warning' => 'bg-warning text-warning-foreground',
                            'danger' => 'bg-danger text-danger-foreground',
                            'accent' => 'bg-accent text-accent-foreground',
                            default => 'bg-neutral-200 text-ink',
                        } . '">' . e($order->payment_status->label()) . '</span>' !!}</dd>
                </div>
                <div>
                    <dt class="text-muted">{{ __('admin.orders.column_placed_at') }}</dt>
                    <dd class="mt-1 text-ink">{{ $order->placed_at?->translatedFormat('Y-m-d H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted">{{ __('admin.orders.column_total') }}</dt>
                    <dd class="mt-1 text-ink">{{ money($order->grand_total) }}</dd>
                </div>
            </dl>
        </x-admin.section>

        {{-- Customer & address --}}
        <x-admin.section :title="__('admin.orders.section_customer')" class="mt-6">
            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted">{{ __('admin.orders.column_customer') }}</dt>
                    <dd class="mt-1 text-ink">{{ $order->user?->name ?? __('admin.orders.customer_guest_label') }}</dd>
                    <dd class="text-muted" dir="ltr">{{ $order->user?->email ?? $order->guest_email }}</dd>
                </div>
                <div>
                    <dt class="text-muted">{{ __('admin.orders.customer_phone_label') }}</dt>
                    <dd class="mt-1 text-ink" dir="ltr">{{ $order->guest_phone ?? $address['phone'] ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-muted">{{ __('admin.orders.address_label') }}</dt>
                    <dd class="mt-1 text-ink">
                        {{ $address['full_name'] ?? '' }} —
                        {{ data_get($address, "governorate.{$locale}") }},
                        {{ data_get($address, "city.{$locale}") }},
                        {{ $address['street'] ?? '' }}
                        @if (!empty($address['building'])) — {{ __('admin.orders.address_label') }} {{ $address['building'] }} @endif
                        @if (!empty($address['landmark'])) ({{ $address['landmark'] }}) @endif
                    </dd>
                </div>
            </dl>
        </x-admin.section>

        {{-- Items --}}
        <x-admin.section :title="__('admin.orders.section_items')" class="mt-6">
            <div class="overflow-x-auto rounded-xl border border-line">
                <table class="w-full text-start text-sm">
                    <thead class="border-b border-line bg-surface">
                        <tr>
                            <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.item_column_product') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.item_column_options') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.item_column_sku') }}</th>
                            <th class="px-3 py-2 text-end font-medium text-muted">{{ __('admin.orders.item_column_price') }}</th>
                            <th class="px-3 py-2 text-center font-medium text-muted">{{ __('admin.orders.item_column_quantity') }}</th>
                            <th class="px-3 py-2 text-end font-medium text-muted">{{ __('admin.orders.item_column_total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr class="border-b border-line last:border-0">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        @if ($item->image_path)
                                            <img
                                                data-order-item-image
                                                src="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($item->image_path) }}"
                                                width="40" height="40"
                                                style="aspect-ratio:1/1"
                                                class="h-10 w-10 shrink-0 rounded object-cover"
                                                alt=""
                                            >
                                        @else
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-surface text-muted" style="width:2.5rem;height:2.5rem" width="40" height="40">
                                                <x-ui.icon name="menu" class="h-4 w-4" />
                                            </span>
                                        @endif
                                        <span class="text-ink">{{ data_get($item->product_name, $locale, data_get($item->product_name, 'ar')) }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-ink" dir="ltr">{{ data_get($item->variant_options, $locale, data_get($item->variant_options, 'ar', '—')) }}</td>
                                <td class="px-3 py-2 text-ink" dir="ltr">{{ $item->sku }}</td>
                                <td class="px-3 py-2 text-end text-ink">{{ money($item->unit_price) }}</td>
                                <td class="px-3 py-2 text-center text-ink">{{ $item->quantity }}</td>
                                <td class="px-3 py-2 text-end text-ink">{{ money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.section>

        {{-- Amounts --}}
        <x-admin.section :title="__('admin.orders.section_amounts')" class="mt-6">
            <dl class="ms-auto max-w-xs space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-muted">{{ __('admin.orders.amount_subtotal') }}</dt><dd class="text-ink">{{ money($order->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ __('admin.orders.amount_discount') }}</dt><dd class="text-ink">{{ money($order->discount_total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ __('admin.orders.amount_shipping') }}</dt><dd class="text-ink">{{ money($order->shipping_total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ __('admin.orders.amount_tax') }}</dt><dd class="text-ink">{{ money($order->tax_total) }}</dd></div>
                <div class="flex justify-between border-t border-line pt-1.5 font-medium"><dt class="text-ink">{{ __('admin.orders.amount_grand_total') }}</dt><dd class="text-ink">{{ money($order->grand_total) }}</dd></div>
            </dl>

            <div class="mt-4 text-sm">
                <p class="text-muted">{{ __('admin.orders.coupon_label') }}</p>
                <p class="text-ink" dir="ltr">{{ $order->coupon_code ?? __('admin.orders.coupon_none') }}</p>
            </div>
        </x-admin.section>

        {{-- Status transition --}}
        <x-admin.section :title="__('admin.orders.transition_title')" class="mt-6">
            @if ($availableTransitions === [])
                <p class="text-sm text-muted">{{ __('admin.orders.transition_none_available') }}</p>
            @else
                <x-admin.form :action="route('admin.orders.transition', $order->id)" method="POST" class="max-w-md space-y-4" data-order-transition-form>
                    <x-form.select
                        name="status"
                        :label="__('admin.orders.transition_status_label')"
                        :options="collect($availableTransitions)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
                        :placeholder="__('admin.orders.transition_status_label')"
                        required
                        data-order-transition-status
                    />
                    <x-form.textarea
                        name="comment"
                        :label="__('admin.orders.transition_comment_label')"
                        :hint="__('admin.orders.transition_comment_required_hint')"
                        required
                    />
                    <x-ui.button type="submit" data-order-transition-submit>{{ __('admin.orders.transition_submit_button') }}</x-ui.button>
                </x-admin.form>
            @endif
        </x-admin.section>

        {{-- Status history --}}
        <x-admin.section :title="__('admin.orders.section_history')" class="mt-6">
            <div class="overflow-x-auto rounded-xl border border-line">
                <table class="w-full text-start text-sm">
                    <thead class="border-b border-line bg-surface">
                        <tr>
                            <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.history_column_date') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.history_column_transition') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.history_column_comment') }}</th>
                            <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.history_column_by') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->statusHistories as $history)
                            <tr class="border-b border-line last:border-0">
                                <td class="px-3 py-2 text-ink">{{ $history->created_at->translatedFormat('Y-m-d H:i') }}</td>
                                <td class="px-3 py-2 text-ink" dir="ltr">{{ $history->from_status?->label() ?? '—' }} → {{ $history->to_status->label() }}</td>
                                <td class="px-3 py-2 text-ink">{{ $history->comment ?? '—' }}</td>
                                <td class="px-3 py-2 text-ink">{{ $history->changedBy?->name ?? __('admin.orders.history_by_system') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.section>

        {{-- Shipments --}}
        <x-admin.section :title="__('admin.orders.section_shipments')" class="mt-6">
            @if ($order->shipments->isEmpty())
                <p class="text-sm text-muted">{{ __('admin.orders.shipment_none') }}</p>
            @else
                <div class="space-y-2">
                    @foreach ($order->shipments as $shipment)
                        <div class="rounded-xl border border-line p-3" data-shipment-row>
                            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <div class="flex flex-wrap items-center gap-4">
                                    <span class="text-ink">{{ $shipment->carrier }}</span>
                                    <span class="text-muted" dir="ltr">
                                        @if ($shipment->tracking_url)
                                            <a href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener" class="text-primary hover:underline">{{ $shipment->tracking_number ?? $shipment->tracking_url }}</a>
                                        @else
                                            {{ $shipment->tracking_number ?? '—' }}
                                        @endif
                                    </span>
                                    <span class="text-ink">{{ money($shipment->cost) }}</span>
                                    <span class="text-muted">{{ $shipment->shipped_at?->translatedFormat('Y-m-d') ?? '—' }}</span>
                                </div>
                                <button type="button" data-shipment-toggle-edit class="text-primary hover:underline">{{ __('admin.orders.shipment_edit_button') }}</button>
                            </div>

                            <div class="mt-3" hidden data-shipment-edit-form>
                                @include('admin.orders._shipment-form', [
                                    'order' => $order,
                                    'shipment' => $shipment,
                                    'action' => route('admin.orders.shipments.update', [$order->id, $shipment->id]),
                                    'method' => 'PUT',
                                ])
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-3">
                <button type="button" data-shipment-toggle-add class="text-sm font-medium text-primary hover:underline">{{ __('admin.orders.shipment_add_button') }}</button>
            </div>

            <div class="mt-3" hidden data-shipment-add-form>
                @include('admin.orders._shipment-form', [
                    'order' => $order,
                    'shipment' => null,
                    'action' => route('admin.orders.shipments.store', $order->id),
                    'method' => 'POST',
                ])
            </div>
        </x-admin.section>

        {{-- Payments (read-only) --}}
        <x-admin.section :title="__('admin.orders.section_payments')" class="mt-6">
            @if ($order->payments->isEmpty())
                <p class="text-sm text-muted">{{ __('admin.orders.payment_none') }}</p>
            @else
                <div class="overflow-x-auto rounded-xl border border-line">
                    <table class="w-full text-start text-sm">
                        <thead class="border-b border-line bg-surface">
                            <tr>
                                <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.payment_column_method') }}</th>
                                <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.payment_column_amount') }}</th>
                                <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.payment_column_status') }}</th>
                                <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.orders.payment_column_date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->payments as $payment)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-3 py-2 text-ink">{{ $payment->method }}</td>
                                    <td class="px-3 py-2 text-ink">{{ money($payment->amount) }}</td>
                                    <td class="px-3 py-2 text-ink">{{ $payment->status }}</td>
                                    <td class="px-3 py-2 text-ink">{{ $payment->created_at->translatedFormat('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.section>

        {{-- Related inventory movements --}}
        <x-admin.section :title="__('admin.orders.section_movements')" class="mt-6">
            @if ($order->inventoryMovements->isEmpty())
                <p class="text-sm text-muted">{{ __('admin.orders.movement_none') }}</p>
            @else
                <div class="overflow-x-auto rounded-xl border border-line">
                    <table class="w-full text-start text-sm">
                        <thead class="border-b border-line bg-surface">
                            <tr>
                                <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.inventory.movement_column_date') }}</th>
                                <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.inventory.movement_column_variant') }}</th>
                                <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.inventory.movement_column_type') }}</th>
                                <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.inventory.movement_column_quantity') }}</th>
                                <th class="px-3 py-2 text-start font-medium text-muted">{{ __('admin.inventory.movement_column_admin') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->inventoryMovements as $movement)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-3 py-2 text-ink">{{ $movement->created_at->translatedFormat('Y-m-d H:i') }}</td>
                                    <td class="px-3 py-2 text-ink" dir="ltr">{{ $movement->variant?->sku ?? '—' }}</td>
                                    <td class="px-3 py-2 text-ink">{{ $movement->type->label() }}</td>
                                    <td class="px-3 py-2 text-ink">{{ $movement->quantity }}</td>
                                    <td class="px-3 py-2 text-ink">{{ $movement->admin?->name ?? __('admin.inventory.movement_admin_system') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <a href="{{ route('admin.inventory.index', ['q' => $order->items->first()?->sku]) }}" class="text-sm text-primary hover:underline">
                        {{ __('admin.orders.returned_stock_notice_link') }}
                    </a>
                </div>
            @endif
        </x-admin.section>

        {{-- Admin note --}}
        <x-admin.section :title="__('admin.orders.section_note')" class="mt-6">
            <x-admin.form :action="route('admin.orders.note', $order->id)" method="PATCH" class="max-w-lg space-y-3">
                <x-form.textarea name="admin_note" :value="$order->admin_note" />
                <x-ui.button type="submit">{{ __('admin.orders.note_save_button') }}</x-ui.button>
            </x-admin.form>
        </x-admin.section>

        <div class="mt-6">
            <x-ui.button variant="outline" :href="route('admin.orders.index')">{{ __('admin.table.back_to_list') }}</x-ui.button>
        </div>
    </x-admin.page>

    <script type="module">
        $('[data-order-item-image]').on('error', function () {
            const $img = $(this);
            const $placeholder = $(
                '<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-surface text-muted" style="width:2.5rem;height:2.5rem"></span>'
            );
            $img.replaceWith($placeholder);
        });

        $('[data-order-transition-form]').on('submit', function (event) {
            const status = $(this).find('[data-order-transition-status]').val();
            let message = null;

            if (status === 'cancelled') {
                message = @json(__('admin.orders.transition_confirm_cancelled'));
            } else if (status === 'returned') {
                message = @json(__('admin.orders.transition_confirm_returned'));
            }

            if (!message) return;

            if (!window.Dersey?.confirmAction) return;

            event.preventDefault();
            window.Dersey.confirmAction(message, () => {
                this.submit();
            });
        });

        $('[data-shipment-toggle-add]').on('click', function () {
            $('[data-shipment-add-form]').prop('hidden', function (i, current) { return !current; });
        });

        $(document).on('click', '[data-shipment-toggle-edit]', function () {
            $(this).closest('[data-shipment-row]').find('[data-shipment-edit-form]')
                .prop('hidden', function (i, current) { return !current; });
        });
    </script>
@endsection
