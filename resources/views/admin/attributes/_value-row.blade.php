{{--
    Shared between the real, server-rendered rows (one per already-saved
    AttributeValue, $value set) and the single blank row template
    admin/repeater.js clones for "add" ($value null, $index === '__INDEX__')
    - keeping both in one partial is what guarantees they can never drift
    out of sync with each other.
--}}
@props(['index', 'value' => null])

<div data-repeater-row class="flex items-start gap-2 rounded-lg border border-line bg-canvas p-3">
    <span data-repeater-drag-handle class="mt-2.5 cursor-grab text-muted" title="{{ __('admin.categories.drag_handle') }}">
        <x-ui.icon name="menu" class="h-4 w-4" />
    </span>

    @if ($value)
        <input type="hidden" name="values[{{ $index }}][id]" value="{{ $value->id }}">
    @endif
    <input type="hidden" data-repeater-delete name="values[{{ $index }}][delete]" value="0">
    <input type="hidden" data-repeater-sort-input name="values[{{ $index }}][sort]" value="{{ old('values.'.$index.'.sort', $value->sort ?? 0) }}">

    <div class="grid flex-1 grid-cols-1 gap-2 sm:grid-cols-3">
        <x-form.input
            :name="'values['.$index.'][translations][ar][value]'"
            :label="__('admin.attributes.field_value_ar')"
            :value="old('values.'.$index.'.translations.ar.value', optional($value?->translate('ar'))->value)"
        />

        <x-form.input
            :name="'values['.$index.'][translations][en][value]'"
            :label="__('admin.attributes.field_value_en')"
            dir="ltr"
            :value="old('values.'.$index.'.translations.en.value', optional($value?->translate('en'))->value)"
        />

        <x-form.input
            type="color"
            :name="'values['.$index.'][color_hex]'"
            :label="__('admin.attributes.field_color_hex')"
            :value="old('values.'.$index.'.color_hex', $value->color_hex ?? '#000000')"
        />
    </div>

    @if ($value && ! $value->canBeDeleted())
        <span
            class="mt-2 cursor-not-allowed rounded-lg p-1.5 text-muted opacity-40"
            title="{{ __('admin.table.delete_disabled_tooltip', ['reason' => implode(', ', array_map('__', $value->deletionBlockers()))]) }}"
        >
            <x-ui.icon name="trash" class="h-4 w-4" />
        </span>
    @else
        <button
            type="button"
            data-repeater-remove
            class="mt-2 rounded-lg p-1.5 text-muted transition-colors duration-150 ease-smooth hover:bg-surface hover:text-danger motion-reduce:transition-none"
        >
            <x-ui.icon name="trash" class="h-4 w-4" />
        </button>
    @endif
</div>
