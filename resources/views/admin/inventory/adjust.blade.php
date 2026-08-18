@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.inventory.adjust_button')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.inventory.title'), 'href' => route('admin.inventory.index')],
            ['label' => __('admin.inventory.adjust_button')],
        ]"
    >
        <p class="mb-4 text-sm text-muted">
            {{ $variant->product->translate('ar')?->name }} — <span dir="ltr">{{ $variant->sku }}</span>
            — {{ __('admin.inventory.column_stock') }}: <strong>{{ $variant->stock_quantity }}</strong>
        </p>

        <x-admin.form :action="route('admin.inventory.adjust', $variant->id)" method="POST" class="max-w-md space-y-6" data-adjustment-form>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-ink">{{ __('admin.inventory.adjust_type_label') }}</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input type="radio" name="type" value="in" data-adjustment-type checked>
                        {{ __('admin.inventory.adjust_type_in') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input type="radio" name="type" value="out" data-adjustment-type>
                        {{ __('admin.inventory.adjust_type_out') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input type="radio" name="type" value="adjust" data-adjustment-type>
                        {{ __('admin.inventory.adjust_type_adjust') }}
                    </label>
                </div>
            </div>

            <div data-adjustment-field="quantity">
                <x-form.input name="quantity" type="number" min="1" step="1" :label="__('admin.inventory.adjust_quantity_label')" required />
            </div>

            <div data-adjustment-field="new_count" hidden>
                <x-form.input name="new_count" type="number" min="0" step="1" :label="__('admin.inventory.adjust_new_count_label')" />
            </div>

            <x-form.textarea
                name="note"
                :label="__('admin.inventory.adjust_note_label')"
                :hint="__('admin.inventory.adjust_note_required_hint')"
                required
            />

            <div class="flex items-center gap-2">
                <x-ui.button type="submit">{{ __('admin.table.save') }}</x-ui.button>
                <x-ui.button variant="outline" :href="route('admin.inventory.index')">{{ __('admin.table.back_to_list') }}</x-ui.button>
            </div>
        </x-admin.form>

        {{--
            Batch 3.3-fix: was plain vanilla JS (document.querySelector/
            addEventListener) - wrong per CLAUDE.md §13, which makes
            jQuery the sole owner of every interaction in the project, not
            just inline scripts that happen to already touch jQuery/
            window.Dersey. type="module" is a separate, additional
            requirement for any such inline script (bundle loads with
            defer, so plain script tags can't rely on jQuery being ready
            yet) - it does not make an interaction "not need" jQuery.
            Delegated ($(document).on(...)), not bound directly to the
            radios, matching this project's own convention everywhere
            else (see product-variants.js, category-tree.js, ...).
        --}}
        <script type="module">
            $(document).on('change', '[data-adjustment-form] [data-adjustment-type]', function () {
                const $radio = $(this);
                const $form = $radio.closest('[data-adjustment-form]');
                const $quantityField = $form.find('[data-adjustment-field="quantity"]');
                const $newCountField = $form.find('[data-adjustment-field="new_count"]');
                const isStocktake = $radio.val() === 'adjust' && $radio.is(':checked');

                $quantityField.prop('hidden', isStocktake);
                $newCountField.prop('hidden', !isStocktake);
                $quantityField.find('input[name="quantity"]').prop('required', !isStocktake);
                $newCountField.find('input[name="new_count"]').prop('required', isStocktake);
            });
        </script>
    </x-admin.page>
@endsection
