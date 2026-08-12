@php
    $locales = ['ar' => __('admin.form.locale_ar'), 'en' => __('admin.form.locale_en')];
    $missingLocales = collect($locales)->keys()->mapWithKeys(fn ($locale) => [
        $locale => $model->exists && blank(optional($model->translate($locale))->name),
    ])->all();
@endphp

<x-admin.section :title="__('admin.attributes.field_code')">
    @if ($model->exists)
        <x-form.input
            :label="__('admin.attributes.field_code')"
            dir="ltr"
            value="{{ $model->code }}"
            disabled
            :hint="__('admin.attributes.field_code_locked_hint')"
        />
    @else
        <x-form.input
            name="code"
            :label="__('admin.attributes.field_code')"
            dir="ltr"
            :value="old('code')"
            :error="$errors->first('code')"
            required
        />
    @endif

    <x-form.select
        name="type"
        :label="__('admin.attributes.field_type')"
        :options="collect(App\Enums\AttributeType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()"
        :value="old('type', $model->type?->value)"
        :error="$errors->first('type')"
        required
    />
</x-admin.section>

<x-admin.section :title="__('admin.attributes.field_name')">
    <x-admin.translatable-tabs id="attribute-i18n" :locales="$locales" :missing="$missingLocales">
        @foreach ($locales as $locale => $label)
            <div id="attribute-i18n-panel-{{ $locale }}" data-tab-panel="{{ $locale }}" @if ($locale !== array_key_first($locales)) hidden @endif class="space-y-4">
                <x-form.input
                    :name="'translations['.$locale.'][name]'"
                    :label="__('admin.attributes.field_name')"
                    :value="old('translations.'.$locale.'.name', optional($model->translate($locale))->name)"
                    :error="$errors->first('translations.'.$locale.'.name')"
                    required
                />

                <x-form.input
                    :name="'translations['.$locale.'][unit]'"
                    :label="__('admin.attributes.field_unit')"
                    :value="old('translations.'.$locale.'.unit', optional($model->translate($locale))->unit)"
                    :error="$errors->first('translations.'.$locale.'.unit')"
                />
            </div>
        @endforeach
    </x-admin.translatable-tabs>
</x-admin.section>

<x-admin.section>
    <input type="hidden" name="is_filterable" value="0">
    <x-form.toggle name="is_filterable" :label="__('admin.attributes.field_is_filterable')" :checked="old('is_filterable', $model->is_filterable ?? true)" />

    @php $isVariantLocked = $model->exists && $model->isUsedByVariants(); @endphp

    <input type="hidden" name="is_variant" value="0">
    <x-form.toggle
        name="is_variant"
        :label="__('admin.attributes.field_is_variant')"
        :checked="old('is_variant', $model->is_variant ?? false)"
        :disabled="$isVariantLocked"
    />
    @if ($isVariantLocked)
        {{-- A disabled checkbox never submits, so without this the "0"
             hidden fallback above would always win and silently flip
             is_variant to false on every save - re-asserting the current
             value here keeps it a true no-op instead. --}}
        <input type="hidden" name="is_variant" value="{{ $model->is_variant ? '1' : '0' }}">
        <p class="mt-1.5 text-xs text-danger">{{ __('admin.attributes.is_variant_locked_hint') }}</p>
    @endif

    <input type="hidden" name="is_required" value="0">
    <x-form.toggle name="is_required" :label="__('admin.attributes.field_is_required')" :checked="old('is_required', $model->is_required ?? false)" />

    <input type="hidden" name="is_active" value="0">
    <x-form.toggle name="is_active" :label="__('admin.attributes.field_is_active')" :checked="old('is_active', $model->is_active ?? true)" />

    <x-form.input
        type="number"
        name="sort"
        :label="__('admin.attributes.field_sort')"
        :value="old('sort', $model->sort ?? 0)"
        min="0"
    />
</x-admin.section>

@if ($model->exists)
    <x-admin.section :title="__('admin.attributes.field_values')">
        <x-admin.repeater name="values" :label="__('admin.attributes.field_values')" sortable>
            @foreach ($model->values()->orderBy('sort')->with('translations')->get() as $value)
                @include('admin.attributes._value-row', ['index' => $value->id, 'value' => $value])
            @endforeach

            <x-slot:emptyRow>
                @include('admin.attributes._value-row', ['index' => '__INDEX__', 'value' => null])
            </x-slot:emptyRow>
        </x-admin.repeater>
    </x-admin.section>
@else
    <x-admin.section :title="__('admin.attributes.field_values')">
        <p class="text-sm text-muted">{{ __('admin.attributes.values_save_first_hint') }}</p>
    </x-admin.section>
@endif
