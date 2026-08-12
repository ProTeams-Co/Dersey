@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.products.title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.products.title')],
        ]"
    >
        <x-slot:actions>
            @if (auth('admin')->user()?->can('products.create'))
                <x-ui.button :href="route('admin.products.create')" size="sm">
                    {{ __('admin.table.create') }}
                </x-ui.button>
            @endif
        </x-slot:actions>

        <x-admin.table :table="$table" />
    </x-admin.page>
@endsection
