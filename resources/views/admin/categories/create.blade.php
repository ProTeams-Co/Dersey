@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.categories.create_title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.categories.title'), 'href' => route('admin.categories.index')],
            ['label' => __('admin.categories.create_title')],
        ]"
    >
        <x-admin.form :action="route('admin.categories.store')" translatable class="max-w-2xl space-y-6">
            @include('admin.categories._form')

            <div class="flex items-center gap-2">
                <x-ui.button type="submit">{{ __('admin.table.save') }}</x-ui.button>
                <x-ui.button variant="outline" :href="route('admin.categories.index')">{{ __('admin.table.back_to_list') }}</x-ui.button>
            </div>
        </x-admin.form>
    </x-admin.page>
@endsection
