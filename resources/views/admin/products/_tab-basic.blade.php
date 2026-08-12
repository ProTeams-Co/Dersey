@php
    $blockers = $model->publicationBlockers();
    // Money::format() appends a currency suffix ("199.50 ج.م") meant for
    // display, not a type="number" input value - a plain decimal string
    // matching what base_price's own validation regex (/^\d+(\.\d{1,2})?$/)
    // expects on submit.
    $toDecimal = fn (?App\Support\Money $money) => $money === null ? null : number_format($money->minor() / 100, 2, '.', '');
@endphp

<x-admin.form :action="route('admin.products.update', $model->id)" method="PUT" class="max-w-2xl space-y-6" data-no-redirect data-product-tab-form="basic">
    <x-admin.section>
        <x-form.select
            name="status"
            :label="__('admin.products.column_status')"
            :options="collect(App\Enums\ProductStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()"
            :value="old('status', $model->status->value)"
            :error="$errors->first('status')"
        />

        @if ($blockers !== [])
            <p class="mt-1.5 text-xs text-danger">{{ __('admin.products.publish_blockers_title') }}</p>
        @endif

        <input type="hidden" name="is_featured" value="0">
        <x-form.toggle name="is_featured" :label="__('admin.products.field_is_featured')" :checked="old('is_featured', $model->is_featured)" />

        <input type="hidden" name="is_new" value="0">
        <x-form.toggle name="is_new" :label="__('admin.products.field_is_new')" :checked="old('is_new', $model->is_new)" />

        <x-form.select
            name="primary_category_id"
            :label="__('admin.products.field_primary_category')"
            :placeholder="__('admin.products.field_primary_category_none')"
            :options="$categoryOptions"
            :value="old('primary_category_id', $model->primary_category_id)"
            :error="$errors->first('primary_category_id')"
            required
        />

        @php
            $additionalCategoryIds = $model->categories()->where('categories.id', '!=', $model->primary_category_id)->pluck('categories.id')->all();
        @endphp
        <div>
            <p class="mb-1.5 block text-sm font-medium text-ink">{{ __('admin.products.field_category_ids') }}</p>
            <div class="max-h-48 space-y-1 overflow-y-auto rounded-lg border border-interactive p-3">
                @foreach ($categoryOptions as $categoryId => $label)
                    @if ($categoryId !== $model->primary_category_id)
                        <label class="flex items-center gap-2 text-sm text-ink">
                            <input type="checkbox" name="category_ids[]" value="{{ $categoryId }}" @checked(in_array($categoryId, $additionalCategoryIds, true))>
                            {{ $label }}
                        </label>
                    @endif
                @endforeach
            </div>
            {{-- An unchecked-everything submit still needs `category_ids`
                 present as an array (not entirely absent) - otherwise
                 request()->has('category_ids') is false and this tab's
                 save silently skips clearing additional categories
                 instead of actually clearing them. The empty-string
                 placeholder is stripped server-side before it ever
                 reaches ProductService - see
                 ProductsController::beforeSave(). --}}
            <input type="hidden" name="category_ids[]" value="">
        </div>

        <x-form.select
            name="brand_id"
            :label="__('admin.products.field_brand')"
            :placeholder="__('admin.categories.field_parent_none')"
            :options="$brandOptions"
            :value="old('brand_id', $model->brand_id)"
            :error="$errors->first('brand_id')"
        />

        <x-form.input
            name="sku"
            :label="__('admin.products.field_sku')"
            dir="ltr"
            :value="old('sku', $model->sku)"
            :error="$errors->first('sku')"
            data-sku-check
            data-sku-current="{{ $model->sku }}"
            data-sku-ignore-id="{{ $model->id }}"
            required
        />
        <p class="mt-1.5 text-xs text-muted" data-sku-status></p>

        <x-form.input
            type="number" step="0.01" min="0"
            name="base_price"
            :label="__('admin.products.field_base_price')"
            :value="old('base_price', $toDecimal($model->base_price))"
            :error="$errors->first('base_price')"
            required
        />

        <x-form.input
            type="number" step="0.01" min="0"
            name="compare_at_price"
            :label="__('admin.products.field_compare_at_price')"
            :value="old('compare_at_price', $toDecimal($model->compare_at_price))"
            :error="$errors->first('compare_at_price')"
        />

        <x-form.input
            type="number" step="0.01" min="0"
            name="cost_price"
            :label="__('admin.products.field_cost_price')"
            :value="old('cost_price', $toDecimal($model->cost_price))"
            :error="$errors->first('cost_price')"
        />

        <x-form.select
            name="gender"
            :label="__('admin.products.field_gender')"
            :options="collect(App\Enums\Gender::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()"
            :value="old('gender', $model->gender->value)"
            :error="$errors->first('gender')"
            required
        />

        <x-form.input
            name="season"
            :label="__('admin.products.field_season')"
            :value="old('season', $model->season)"
            :error="$errors->first('season')"
        />

        <x-form.input
            type="number" min="1"
            name="weight"
            :label="__('admin.products.field_weight')"
            :value="old('weight', $model->weight)"
            :error="$errors->first('weight')"
            required
        />
    </x-admin.section>

    <x-ui.button type="submit">{{ __('admin.products.save_tab') }}</x-ui.button>
</x-admin.form>
