@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.inventory.title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.inventory.title')],
        ]"
    >
        <x-slot:actions>
            <span class="rounded-full bg-warning/10 px-2.5 py-1 text-xs font-medium text-warning">
                {{ __('admin.inventory.counter_low', ['count' => $lowCount]) }}
            </span>
            <span class="rounded-full bg-danger/10 px-2.5 py-1 text-xs font-medium text-danger">
                {{ __('admin.inventory.counter_out', ['count' => $outCount]) }}
            </span>
        </x-slot:actions>

        <x-admin.table :table="$table" />
    </x-admin.page>
@endsection
