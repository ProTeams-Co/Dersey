@php
    $locales = ['ar' => __('admin.form.locale_ar'), 'en' => __('admin.form.locale_en')];
    $missingLocales = collect($locales)->keys()->mapWithKeys(fn ($locale) => [
        $locale => blank(optional($model->translate($locale))->name),
    ])->all();
@endphp

<x-admin.form :action="route('admin.products.update', $model->id)" method="PUT" translatable class="max-w-2xl space-y-6" data-no-redirect data-product-tab-form="translations">
    <x-admin.translatable-tabs id="product-i18n" :locales="$locales" :missing="$missingLocales">
        @foreach ($locales as $locale => $label)
            <div id="product-i18n-panel-{{ $locale }}" data-tab-panel="{{ $locale }}" @if ($locale !== array_key_first($locales)) hidden @endif class="space-y-4">
                <x-form.input
                    :name="'translations['.$locale.'][name]'"
                    :label="__('admin.products.field_name')"
                    :value="old('translations.'.$locale.'.name', optional($model->translate($locale))->name)"
                    :error="$errors->first('translations.'.$locale.'.name')"
                    data-slug-source="{{ $locale }}"
                    required
                />

                <x-form.input
                    :name="'translations['.$locale.'][slug]'"
                    :label="__('admin.products.field_slug')"
                    dir="ltr"
                    :value="old('translations.'.$locale.'.slug', optional($model->translate($locale))->slug)"
                    :hint="__('admin.form.slug_change_warning')"
                    :error="$errors->first('translations.'.$locale.'.slug')"
                />

                <x-form.textarea
                    :name="'translations['.$locale.'][short_description]'"
                    :label="__('admin.products.field_short_description')"
                    :value="old('translations.'.$locale.'.short_description', optional($model->translate($locale))->short_description)"
                    :error="$errors->first('translations.'.$locale.'.short_description')"
                />

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-ink">{{ __('admin.products.field_description') }}</label>
                    {{-- data-editor-lazy, not data-editor - editor.js's own
                         global init() runs once at page load and would
                         initialize this immediately (it's in the DOM from
                         the start, just visually hidden by the inactive
                         tab panel), which is not "lazy until the tab
                         opens". admin/product-form.js calls
                         Editor.initEditor() on this element directly the
                         first time this tab is actually shown. --}}
                    <textarea
                        name="translations[{{ $locale }}][description]"
                        data-editor-lazy
                        data-editor-content-lang="{{ $locale }}"
                        data-editor-upload-url="{{ route('admin.media.store') }}"
                        rows="8"
                    >{{ old('translations.'.$locale.'.description', optional($model->translate($locale))->description) }}</textarea>
                    @error('translations.'.$locale.'.description')
                        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <x-form.input
                    :name="'translations['.$locale.'][material]'"
                    :label="__('admin.products.field_material')"
                    :value="old('translations.'.$locale.'.material', optional($model->translate($locale))->material)"
                    :error="$errors->first('translations.'.$locale.'.material')"
                />

                <x-form.textarea
                    :name="'translations['.$locale.'][care_instructions]'"
                    :label="__('admin.products.field_care_instructions')"
                    :value="old('translations.'.$locale.'.care_instructions', optional($model->translate($locale))->care_instructions)"
                    :error="$errors->first('translations.'.$locale.'.care_instructions')"
                />
            </div>
        @endforeach
    </x-admin.translatable-tabs>

    <x-ui.button type="submit">{{ __('admin.products.save_tab') }}</x-ui.button>
</x-admin.form>
