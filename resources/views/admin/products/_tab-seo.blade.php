@php
    $locales = ['ar' => __('admin.form.locale_ar'), 'en' => __('admin.form.locale_en')];
@endphp

<x-admin.form :action="route('admin.products.update', $model->id)" method="PUT" translatable class="max-w-2xl space-y-6" data-no-redirect data-product-tab-form="seo">
    <x-admin.translatable-tabs id="product-seo-i18n" :locales="$locales">
        @foreach ($locales as $locale => $label)
            @php $seo = $model->exists ? $model->seoMetas()->where('locale', $locale)->first() : null; @endphp
            <div id="product-seo-i18n-panel-{{ $locale }}" data-tab-panel="{{ $locale }}" @if ($locale !== array_key_first($locales)) hidden @endif class="space-y-4">
                <x-form.input
                    :name="'seo['.$locale.'][title]'"
                    :label="__('admin.products.field_seo_title')"
                    maxlength="255"
                    data-seo-title
                    :value="old('seo.'.$locale.'.title', $seo?->title)"
                    :error="$errors->first('seo.'.$locale.'.title')"
                />

                <x-form.textarea
                    :name="'seo['.$locale.'][description]'"
                    :label="__('admin.products.field_seo_description')"
                    :maxlength="500"
                    data-seo-description
                    :value="old('seo.'.$locale.'.description', $seo?->description)"
                    :error="$errors->first('seo.'.$locale.'.description')"
                />

                <div data-seo-serp-preview class="rounded-lg border border-line bg-surface p-3">
                    <p class="mb-1 text-xs font-medium text-muted">{{ __('admin.products.seo_serp_preview') }}</p>
                    <p class="truncate text-sm text-accent" data-seo-preview-title></p>
                    <p class="line-clamp-2 text-xs text-muted" data-seo-preview-description></p>
                </div>

                <x-admin.media-picker
                    :name="'seo['.$locale.'][og_image]'"
                    :existing="$seo?->og_image ? [$seo->og_image] : []"
                    :label="__('admin.products.field_seo_og_image')"
                />

                <x-form.input
                    :name="'seo['.$locale.'][canonical_url]'"
                    :label="__('admin.products.field_seo_canonical')"
                    dir="ltr"
                    :value="old('seo.'.$locale.'.canonical_url', $seo?->canonical_url)"
                    :error="$errors->first('seo.'.$locale.'.canonical_url')"
                />

                {{-- Hidden fallback BEFORE the checkbox, same name - the
                     browser sends both in DOM order and the server takes
                     the last value, so this must come first for a checked
                     box to actually win (see x-form.toggle's identical
                     convention). Without it, an unchecked box would leave
                     seo.{locale}.robots entirely absent, meaning this tab's
                     save couldn't ever clear a previously-set noindex. --}}
                <input type="hidden" name="seo[{{ $locale }}][robots]" value="index, follow">
                <label class="flex items-center gap-2 text-sm text-ink">
                    <input
                        type="checkbox"
                        name="seo[{{ $locale }}][robots]"
                        value="noindex, nofollow"
                        @checked(old('seo.'.$locale.'.robots', $seo?->robots) === 'noindex, nofollow')
                    >
                    {{ __('admin.products.field_seo_noindex') }}
                </label>
            </div>
        @endforeach
    </x-admin.translatable-tabs>

    <x-ui.button type="submit">{{ __('admin.products.save_tab') }}</x-ui.button>
</x-admin.form>
