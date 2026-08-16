{{--
    Batch 3.2-C. Same shape as _tab-variants.blade.php: server renders the
    CURRENT state once (works with zero JS), and resources/js/admin/product-images.js
    (lazy-loaded the first time this tab opens - see product-form.js) takes
    over from there: upload (its own FilePond instance, hitting the SAME
    shared admin.media.store/destroy temp endpoints MediaUploadController
    already exposes - not a new upload endpoint), link, edit alt/color,
    drag-reorder, set primary, delete.
--}}
<div
    data-image-gallery
    data-store-url="{{ route('admin.products.images.store', $model->id) }}"
    data-reorder-url="{{ route('admin.products.images.reorder', $model->id) }}"
    data-update-url-template="{{ route('admin.products.images.update', [$model->id, '__ID__']) }}"
    data-primary-url-template="{{ route('admin.products.images.primary', [$model->id, '__ID__']) }}"
    data-destroy-url-template="{{ route('admin.products.images.destroy', [$model->id, '__ID__']) }}"
    data-media-upload-url="{{ route('admin.media.store') }}"
    data-media-revert-url="{{ route('admin.media.destroy', ['file' => '__ID__']) }}"
    data-max-images="{{ $productImagesMax }}"
    data-set-primary-label="{{ __('admin.products.image_set_primary_button') }}"
    data-delete-label="{{ __('admin.products.image_delete_button') }}"
    data-link-label="{{ __('admin.products.image_link_button') }}"
    data-pending-label="{{ __('admin.products.image_pending_label') }}"
>
    <x-admin.section :title="__('admin.products.image_upload_label')">
        <p class="mb-2 text-xs text-muted">{{ __('admin.products.image_upload_hint') }}</p>

        <input
            type="file"
            multiple
            accept="image/png,image/jpeg,image/webp"
            data-image-upload-input
        >

        <p class="mt-2 text-sm text-muted">
            <span data-image-counter>{{ $images->count() }}/{{ $productImagesMax }}</span>
        </p>

        <div data-image-pending-list class="mt-4 space-y-3"></div>

        {{-- Cloned by product-images.js for every pending card's color
             <select> - a single source for the option list instead of
             duplicating it in JS, and works correctly even when the
             product currently has zero images (nothing to clone from). --}}
        <template data-image-color-options-template>
            <option value="">{{ __('admin.products.image_no_color_option') }}</option>
            @foreach ($colorOptions as $colorValue)
                <option value="{{ $colorValue->id }}">{{ $colorValue->translate('ar')?->value }}</option>
            @endforeach
        </template>
    </x-admin.section>

    <x-admin.section :title="__('admin.products.tab_images')" class="mt-6">
        <p class="text-sm text-muted" data-image-empty-message @if ($images->isNotEmpty()) hidden @endif>
            {{ __('admin.products.image_empty_message') }}
        </p>

        <div data-image-grid class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($images as $image)
                @include('admin.products._image-card', ['image' => $image, 'colorOptions' => $colorOptions])
            @endforeach
        </div>

        <div class="mt-4" @if ($images->isEmpty()) hidden @endif data-image-save-order-wrap>
            <x-ui.button type="button" data-image-save-order-button>{{ __('admin.products.image_save_order_button') }}</x-ui.button>
        </div>
    </x-admin.section>
</div>
