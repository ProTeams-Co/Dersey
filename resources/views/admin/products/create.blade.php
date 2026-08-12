@extends('layouts.admin')

@section('content')
    <x-admin.page
        :title="__('admin.products.create_title')"
        :breadcrumbs="[
            ['label' => __('admin.layout.breadcrumb_home'), 'href' => route('admin.dashboard')],
            ['label' => __('admin.products.title'), 'href' => route('admin.products.index')],
            ['label' => __('admin.products.create_title')],
        ]"
    >
        <x-admin.form :action="route('admin.products.store')" translatable class="max-w-2xl space-y-6">
            <x-admin.section :title="__('admin.products.field_name')">
                <x-form.input
                    :name="'translations[ar][name]'"
                    :label="__('admin.products.field_name').' ('.__('admin.form.locale_ar').')'"
                    :value="old('translations.ar.name')"
                    :error="$errors->first('translations.ar.name')"
                    data-slug-source="ar"
                    required
                />

                <x-form.input
                    :name="'translations[en][name]'"
                    :label="__('admin.products.field_name').' ('.__('admin.form.locale_en').')'"
                    dir="ltr"
                    :value="old('translations.en.name')"
                    :error="$errors->first('translations.en.name')"
                />
            </x-admin.section>

            <x-admin.section>
                <x-form.input
                    name="sku"
                    :label="__('admin.products.field_sku')"
                    dir="ltr"
                    :value="old('sku')"
                    :error="$errors->first('sku')"
                    data-sku-check
                    required
                />
                <p class="mt-1.5 text-xs text-muted" data-sku-status></p>

                <x-form.input
                    type="number"
                    step="0.01"
                    min="0"
                    name="base_price"
                    :label="__('admin.products.field_base_price')"
                    :value="old('base_price')"
                    :error="$errors->first('base_price')"
                    required
                />

                <x-form.select
                    name="gender"
                    :label="__('admin.products.field_gender')"
                    :options="collect(App\Enums\Gender::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()"
                    :value="old('gender')"
                    :error="$errors->first('gender')"
                    required
                />

                <x-form.input
                    type="number"
                    min="1"
                    name="weight"
                    :label="__('admin.products.field_weight')"
                    :value="old('weight')"
                    :error="$errors->first('weight')"
                    required
                />

                <x-form.select
                    name="brand_id"
                    :label="__('admin.products.field_brand')"
                    :placeholder="__('admin.categories.field_parent_none')"
                    :options="$brandOptions"
                    :value="old('brand_id')"
                    :error="$errors->first('brand_id')"
                />

                <x-form.select
                    name="primary_category_id"
                    :label="__('admin.products.field_primary_category')"
                    :placeholder="__('admin.products.field_primary_category_none')"
                    :options="$categoryOptions"
                    :value="old('primary_category_id')"
                    :error="$errors->first('primary_category_id')"
                    required
                />
            </x-admin.section>

            <div class="flex items-center gap-2">
                <x-ui.button type="submit">{{ __('admin.table.save') }}</x-ui.button>
                <x-ui.button variant="outline" :href="route('admin.products.index')">{{ __('admin.table.back_to_list') }}</x-ui.button>
            </div>
        </x-admin.form>
    </x-admin.page>
@endsection
