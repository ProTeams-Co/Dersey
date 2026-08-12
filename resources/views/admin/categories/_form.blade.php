@php
    $locales = ['ar' => __('admin.form.locale_ar'), 'en' => __('admin.form.locale_en')];
    $missingLocales = collect($locales)->keys()->mapWithKeys(fn ($locale) => [
        $locale => $model->exists && blank(optional($model->translate($locale))->name),
    ])->all();
@endphp

<x-admin.section :title="__('admin.categories.field_parent')">
    <x-form.select
        name="parent_id"
        :label="__('admin.categories.field_parent')"
        :placeholder="__('admin.categories.field_parent_none')"
        :options="$parentOptions"
        :value="old('parent_id', $model->parent_id)"
        :error="$errors->first('parent_id')"
    />
</x-admin.section>

<x-admin.section :title="__('admin.categories.field_name')">
    <x-admin.translatable-tabs id="category-i18n" :locales="$locales" :missing="$missingLocales">
        @foreach ($locales as $locale => $label)
            <div id="category-i18n-panel-{{ $locale }}" data-tab-panel="{{ $locale }}" @if ($locale !== array_key_first($locales)) hidden @endif class="space-y-4">
                <x-form.input
                    :name="'translations['.$locale.'][name]'"
                    :label="__('admin.categories.field_name')"
                    :value="old('translations.'.$locale.'.name', optional($model->translate($locale))->name)"
                    :error="$errors->first('translations.'.$locale.'.name')"
                    required
                />

                <x-form.input
                    :name="'translations['.$locale.'][slug]'"
                    :label="__('admin.categories.field_slug')"
                    dir="ltr"
                    :value="old('translations.'.$locale.'.slug', optional($model->translate($locale))->slug)"
                    :hint="$model->exists ? __('admin.form.slug_change_warning') : null"
                    :error="$errors->first('translations.'.$locale.'.slug')"
                />

                <x-form.textarea
                    :name="'translations['.$locale.'][description]'"
                    :label="__('admin.categories.field_description')"
                    :value="old('translations.'.$locale.'.description', optional($model->translate($locale))->description)"
                    :error="$errors->first('translations.'.$locale.'.description')"
                />

                <x-form.input
                    :name="'translations['.$locale.'][meta_title]'"
                    :label="__('admin.categories.field_meta_title')"
                    :value="old('translations.'.$locale.'.meta_title', optional($model->translate($locale))->meta_title)"
                    :error="$errors->first('translations.'.$locale.'.meta_title')"
                />

                <x-form.textarea
                    :name="'translations['.$locale.'][meta_description]'"
                    :label="__('admin.categories.field_meta_description')"
                    :value="old('translations.'.$locale.'.meta_description', optional($model->translate($locale))->meta_description)"
                    :error="$errors->first('translations.'.$locale.'.meta_description')"
                />
            </div>
        @endforeach
    </x-admin.translatable-tabs>
</x-admin.section>

<x-admin.section :title="__('admin.categories.field_image')">
    <x-admin.media-picker
        name="image"
        :existing="$model->image ? [$model->image] : []"
        :label="__('admin.categories.field_image')"
    />

    <x-admin.media-picker
        name="icon"
        :existing="$model->icon ? [$model->icon] : []"
        :label="__('admin.categories.field_icon')"
    />
</x-admin.section>

<x-admin.section>
    <input type="hidden" name="is_active" value="0">
    <x-form.toggle name="is_active" :label="__('admin.categories.field_is_active')" :checked="old('is_active', $model->is_active ?? true)" />

    <input type="hidden" name="is_featured" value="0">
    <x-form.toggle name="is_featured" :label="__('admin.categories.field_is_featured')" :checked="old('is_featured', $model->is_featured ?? false)" />

    <input type="hidden" name="show_in_menu" value="0">
    <x-form.toggle name="show_in_menu" :label="__('admin.categories.field_show_in_menu')" :checked="old('show_in_menu', $model->show_in_menu ?? true)" />

    <x-form.input
        type="number"
        name="sort"
        :label="__('admin.categories.field_sort')"
        :value="old('sort', $model->sort ?? 0)"
        min="0"
    />
</x-admin.section>
