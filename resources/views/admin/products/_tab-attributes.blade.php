{{--
    Non-variant attributes only (is_variant = false - material, season,
    ...). Variant-generating attributes (size, color) are Batch 3.2-B's
    product_variants, explicitly out of this batch's scope - never
    queried or rendered here at all, not just hidden.
--}}
<x-admin.form :action="route('admin.products.update', $model->id)" method="PUT" class="max-w-2xl space-y-6" data-no-redirect data-product-tab-form="attributes">
    <x-admin.section :title="__('admin.products.field_attribute_values')">
        @forelse ($nonVariantAttributes as $attribute)
            <div>
                <p class="mb-1.5 text-sm font-medium text-ink">{{ $attribute->translate('ar')?->name }}</p>
                <div class="flex flex-wrap gap-3">
                    @foreach ($attribute->values as $value)
                        <label class="flex items-center gap-1.5 text-sm text-ink">
                            <input
                                type="checkbox"
                                name="attribute_value_ids[]"
                                value="{{ $value->id }}"
                                @checked(in_array($value->id, $selectedAttributeValueIds, true))
                            >
                            {{ $value->translate('ar')?->value }}
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-muted">{{ __('admin.table.no_results_title') }}</p>
        @endforelse

        {{-- Same "always present as an array" fallback as the categories
             checklist - see _tab-basic.blade.php's identical comment. --}}
        <input type="hidden" name="attribute_value_ids[]" value="">
    </x-admin.section>

    <x-ui.button type="submit">{{ __('admin.products.save_tab') }}</x-ui.button>
</x-admin.form>
