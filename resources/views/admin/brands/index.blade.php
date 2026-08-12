@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.brands.title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.brands.title')],
        ]"
    >
        <x-slot:actions>
            {{--
                auth('admin')->user()->can(), not @can()/Gate::check() -
                the Blade @can directive resolves against the *default*
                guard (web), same bug as AuthorizesRequests's default
                authorize() (see AdminController). Model::can() (spatie's
                HasRoles + Authorizable) correctly scopes to $this, so
                calling it directly on the authenticated admin sidesteps
                the issue entirely.
            --}}
            @if (auth('admin')->user()?->can('brands.create'))
                <x-ui.button :href="route('admin.brands.create')" size="sm">
                    {{ __('admin.table.create') }}
                </x-ui.button>
            @endif
        </x-slot:actions>

        <x-admin.table :table="$table" />
    </x-admin.page>
@endsection
