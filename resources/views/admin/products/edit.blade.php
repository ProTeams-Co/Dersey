@extends('layouts.admin')

@section('content')
    @php
        $blockers = $model->publicationBlockers();
        $tabs = [
            'basic' => __('admin.products.tab_basic'),
            'translations' => __('admin.products.tab_translations'),
            'attributes' => __('admin.products.tab_attributes'),
            'seo' => __('admin.products.tab_seo'),
            'variants' => __('admin.products.tab_variants'),
            'images' => __('admin.products.tab_images'),
        ];
    @endphp

    <x-admin.page
        :title="__('admin.products.edit_title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.products.title'), 'href' => route('admin.products.index')],
            ['label' => __('admin.products.edit_title')],
        ]"
    >
        <x-slot:actions>
            @if ($model->trashed())
                <form data-ajax-form data-no-redirect action="{{ route('admin.products.restore', $model->id) }}" method="POST">
                    @csrf
                    <x-ui.button type="submit" size="sm">{{ __('admin.products.restore_action') }}</x-ui.button>
                </form>
            @else
                <button
                    type="button"
                    data-publish-button
                    data-publish-url="{{ route('admin.products.status', $model->id) }}"
                    @disabled($blockers !== [])
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground transition-colors duration-150 ease-smooth hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50 motion-reduce:transition-none"
                >
                    {{ __('admin.products.publish_button') }}
                </button>
            @endif
        </x-slot:actions>

        @if ($blockers !== [])
            <x-ui.alert type="warning" class="mb-4">
                <p class="font-medium">{{ __('admin.products.publish_blockers_title') }}</p>
                <ul class="mt-1 list-disc ps-5">
                    @foreach ($blockers as $blocker)
                        <li>{{ __($blocker) }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div data-product-tabs>
            <nav class="mb-4 flex flex-wrap gap-1 border-b border-line" role="tablist">
                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        data-product-tab-trigger="{{ $key }}"
                        class="rounded-t-lg border-b-2 border-transparent px-3 py-2 text-sm font-medium text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </nav>

            <div data-product-tab-panel="basic">
                @include('admin.products._tab-basic')
            </div>

            <div data-product-tab-panel="translations" hidden>
                @include('admin.products._tab-translations')
            </div>

            <div data-product-tab-panel="attributes" hidden>
                @include('admin.products._tab-attributes')
            </div>

            <div data-product-tab-panel="seo" hidden>
                @include('admin.products._tab-seo')
            </div>

            <div data-product-tab-panel="variants" hidden>
                <p class="text-sm text-muted">{{ __('admin.products.coming_soon_variants') }}</p>
            </div>

            <div data-product-tab-panel="images" hidden>
                <p class="text-sm text-muted">{{ __('admin.products.coming_soon_images') }}</p>
            </div>
        </div>

        <div class="mt-6">
            <x-ui.button variant="outline" :href="route('admin.products.index')">{{ __('admin.table.back_to_list') }}</x-ui.button>
        </div>
    </x-admin.page>
@endsection
