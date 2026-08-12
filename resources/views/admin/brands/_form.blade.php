@php
    $locales = ['ar' => __('admin.form.locale_ar'), 'en' => __('admin.form.locale_en')];
    $missingLocales = collect($locales)->keys()->mapWithKeys(fn ($locale) => [
        $locale => $model->exists && blank(optional($model->translate($locale))->name),
    ])->all();
@endphp

<x-admin.section :title="__('admin.brands.field_name')">
    <x-admin.translatable-tabs id="brand-i18n" :locales="$locales" :missing="$missingLocales">
        @foreach ($locales as $locale => $label)
            <div id="brand-i18n-panel-{{ $locale }}" data-tab-panel="{{ $locale }}" @if ($locale !== array_key_first($locales)) hidden @endif class="space-y-4">
                <x-form.input
                    :name="'translations['.$locale.'][name]'"
                    :label="__('admin.brands.field_name')"
                    :value="old('translations.'.$locale.'.name', optional($model->translate($locale))->name)"
                    :error="$errors->first('translations.'.$locale.'.name')"
                    required
                />

                <x-form.input
                    :name="'translations['.$locale.'][slug]'"
                    :label="__('admin.brands.field_slug')"
                    dir="ltr"
                    :value="old('translations.'.$locale.'.slug', optional($model->translate($locale))->slug)"
                    :hint="$model->exists ? __('admin.form.slug_change_warning') : null"
                    :error="$errors->first('translations.'.$locale.'.slug')"
                />

                <x-form.textarea
                    :name="'translations['.$locale.'][description]'"
                    :label="__('admin.brands.field_description')"
                    :value="old('translations.'.$locale.'.description', optional($model->translate($locale))->description)"
                    :error="$errors->first('translations.'.$locale.'.description')"
                />
            </div>
        @endforeach
    </x-admin.translatable-tabs>
</x-admin.section>

<x-admin.section :title="__('admin.brands.field_logo')">
    <x-admin.media-picker
        name="logo"
        :existing="$model->logo ? [$model->logo] : []"
        :label="__('admin.brands.field_logo')"
    />
</x-admin.section>

<x-admin.section>
    <input type="hidden" name="is_active" value="0">
    <x-form.toggle name="is_active" :label="__('admin.brands.field_is_active')" :checked="old('is_active', $model->is_active ?? true)" />

    <input type="hidden" name="is_featured" value="0">
    <x-form.toggle name="is_featured" :label="__('admin.brands.field_is_featured')" :checked="old('is_featured', $model->is_featured ?? false)" />

    <x-form.input
        type="number"
        name="sort"
        :label="__('admin.brands.field_sort')"
        :value="old('sort', $model->sort ?? 0)"
        min="0"
    />
</x-admin.section>
