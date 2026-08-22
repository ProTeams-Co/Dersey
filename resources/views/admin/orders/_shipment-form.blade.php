@props(['order', 'shipment', 'action', 'method'])

@php
    $toDecimal = fn (?App\Support\Money $money) => $money === null ? null : number_format($money->minor() / 100, 2, '.', '');
@endphp

<x-admin.form :action="$action" :method="$method" class="max-w-md space-y-4">
    <x-form.input name="carrier" :label="__('admin.orders.shipment_carrier_label')" :value="$shipment?->carrier" required />
    <x-form.input name="tracking_number" :label="__('admin.orders.shipment_tracking_label')" :value="$shipment?->tracking_number" dir="ltr" />
    <x-form.input name="tracking_url" type="url" :label="__('admin.orders.shipment_url_label')" :value="$shipment?->tracking_url" dir="ltr" />
    <x-form.input name="cost" type="number" step="0.01" min="0" :label="__('admin.orders.shipment_cost_label')" :value="$toDecimal($shipment?->cost)" required />
    <x-form.input name="shipped_at" type="date" :label="__('admin.orders.shipment_shipped_at_label')" :value="$shipment?->shipped_at?->format('Y-m-d')" />

    <x-ui.button type="submit">{{ __('admin.table.save') }}</x-ui.button>
</x-admin.form>
