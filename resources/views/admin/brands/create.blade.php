@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.brands.create_title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.brands.title'), 'href' => route('admin.brands.index')],
            ['label' => __('admin.brands.create_title')],
        ]"
    >
        <x-admin.form :action="route('admin.brands.store')" translatable class="max-w-2xl space-y-6">
            @include('admin.brands._form')

            <div class="flex items-center gap-2">
                <x-ui.button type="submit">{{ __('admin.table.save') }}</x-ui.button>
                <x-ui.button variant="outline" :href="route('admin.brands.index')">{{ __('admin.table.back_to_list') }}</x-ui.button>
            </div>
        </x-admin.form>
    </x-admin.page>
@endsection
