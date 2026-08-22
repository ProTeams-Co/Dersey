@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.orders.title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.orders.title')],
        ]"
    >
        <x-admin.table :table="$table" />
    </x-admin.page>
@endsection
