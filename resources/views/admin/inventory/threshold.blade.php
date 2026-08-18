@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.inventory.threshold_button')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.inventory.title'), 'href' => route('admin.inventory.index')],
            ['label' => __('admin.inventory.threshold_button')],
        ]"
    >
        <p class="mb-4 text-sm text-muted">
            {{ $variant->product->translate('ar')?->name }} — <span dir="ltr">{{ $variant->sku }}</span>
        </p>

        <x-admin.form :action="route('admin.inventory.threshold', $variant->id)" method="PUT" class="max-w-md space-y-6">
            <x-form.input
                name="low_stock_threshold"
                type="number"
                min="0"
                step="1"
                :label="__('admin.inventory.threshold_label')"
                :value="$variant->low_stock_threshold"
                required
            />

            <div class="flex items-center gap-2">
                <x-ui.button type="submit">{{ __('admin.table.save') }}</x-ui.button>
                <x-ui.button variant="outline" :href="route('admin.inventory.index')">{{ __('admin.table.back_to_list') }}</x-ui.button>
            </div>
        </x-admin.form>
    </x-admin.page>
@endsection
