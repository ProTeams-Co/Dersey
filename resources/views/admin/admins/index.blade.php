@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.admins.title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.admins.title')],
        ]"
    >
        <x-admin.table :table="$table" />
    </x-admin.page>
@endsection
