@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.attributes.edit_title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.attributes.title'), 'href' => route('admin.attributes.index')],
            ['label' => __('admin.attributes.edit_title')],
        ]"
    >
        <x-admin.form :action="route('admin.attributes.update', $model->id)" method="PUT" translatable class="max-w-2xl space-y-6">
            @include('admin.attributes._form')

            <div class="flex items-center gap-2">
                <x-ui.button type="submit">{{ __('admin.table.save') }}</x-ui.button>
                <x-ui.button variant="outline" :href="route('admin.attributes.index')">{{ __('admin.table.back_to_list') }}</x-ui.button>
            </div>
        </x-admin.form>
    </x-admin.page>
@endsection
