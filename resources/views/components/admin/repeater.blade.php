{{--
    Dynamic add/remove rows. The caller renders existing rows (server-side,
    one per already-saved item) as the default slot, each wrapped in
    <div data-repeater-row>...fields...<button data-repeater-remove></div>
    - and provides one blank row's markup via the emptyRow slot, which
    admin/table.js/form.js clones (via <template>, so the browser never
    executes/renders it until cloned) when "add" is clicked. No JS-side
    knowledge of what fields a row actually contains - it only ever
    clones/removes a whole row element.
--}}
@props(['name', 'label' => null])

<div data-repeater data-repeater-name="{{ $name }}">
    @if ($label)
        <p class="mb-1.5 text-sm font-medium text-ink">{{ $label }}</p>
    @endif

    <div data-repeater-rows class="space-y-3">
        {{ $slot }}
    </div>

    @isset($emptyRow)
        <template data-repeater-template>{{ $emptyRow }}</template>
    @endisset

    <button
        type="button"
        data-repeater-add
        class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-primary transition-colors duration-150 ease-smooth hover:underline motion-reduce:transition-none"
    >
        <x-ui.icon name="plus" class="h-4 w-4" />
        {{ __('admin.form.add_row') }}
    </button>
</div>
