@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.inventory.movements_title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.inventory.title'), 'href' => route('admin.inventory.index')],
            ['label' => __('admin.inventory.movements_title')],
        ]"
    >
        <x-admin.table :table="$table" />
    </x-admin.page>
@endsection
