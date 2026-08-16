{{--
    Batch 3.2-C. Shared shape between this server-rendered card (existing
    images, on first page load) and product-images.js's buildCard() (a
    freshly-linked image, added without a full reload) - same data
    attributes on both, so every handler in product-images.js (reorder,
    edit, primary, delete) works identically regardless of which one
    produced the card in the DOM.
--}}
@props(['image', 'colorOptions'])

@php
    $url = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($image->path);
    $altAr = $image->getTranslation('alt', 'ar');
    $altEn = $image->getTranslation('alt', 'en');
@endphp

<div
    data-image-card
    data-image-id="{{ $image->id }}"
    class="space-y-2 rounded-xl border border-line p-3"
>
    <div class="flex items-center justify-between">
        <span data-image-drag-handle class="cursor-grab text-muted" title="{{ __('admin.categories.drag_handle') }}">
            <x-ui.icon name="menu" class="h-4 w-4" />
        </span>

        @if ($image->is_primary)
            <span data-image-primary-badge class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                {{ __('admin.products.image_primary_badge') }}
            </span>
        @else
            <button type="button" data-image-set-primary class="text-xs font-medium text-muted hover:text-ink">
                {{ __('admin.products.image_set_primary_button') }}
            </button>
        @endif
    </div>

    <img
        src="{{ $url }}"
        width="{{ $image->width }}"
        height="{{ $image->height }}"
        style="aspect-ratio: {{ $image->width }} / {{ $image->height }}"
        class="w-full rounded-lg object-cover"
        alt="{{ $altAr }}"
    >

    <select data-image-color-select class="w-full rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink">
        <option value="">{{ __('admin.products.image_no_color_option') }}</option>
        @foreach ($colorOptions as $colorValue)
            <option value="{{ $colorValue->id }}" @selected($image->color_value_id === $colorValue->id)>
                {{ $colorValue->translate('ar')?->value }}
            </option>
        @endforeach
    </select>

    <input
        type="text"
        data-image-alt-ar
        value="{{ $altAr }}"
        placeholder="{{ __('admin.products.image_alt_ar_label') }}"
        dir="rtl"
        class="w-full rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink"
    >
    <input
        type="text"
        data-image-alt-en
        value="{{ $altEn }}"
        placeholder="{{ __('admin.products.image_alt_en_label') }}"
        dir="ltr"
        class="w-full rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink"
    >

    <button
        type="button"
        data-image-delete
        class="w-full rounded-lg border border-danger/30 px-2 py-1 text-xs font-medium text-danger hover:bg-danger/10"
    >
        <x-ui.icon name="trash" class="me-1 inline h-3.5 w-3.5" />
        {{ __('admin.products.image_delete_button') }}
    </button>
</div>
