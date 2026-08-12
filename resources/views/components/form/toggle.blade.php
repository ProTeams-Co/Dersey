@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'checked' => false,
])

@php
    $id = $id ?? 'field-'.\Illuminate\Support\Str::random(8);
@endphp

{{--
    Thumb position is a physical transform (translateX doesn't follow dir on
    its own) — rtl: mirrors it so "on" still slides toward the same visual
    side a toggle reader expects in either direction.

    The outer label is `flex` (block-level), not `inline-flex` — an inline
    label flows next to its siblings like a word in a sentence, so a form
    with several toggles in a row crammed them onto shared lines with only
    their own internal gap-2 between them, no matter how much space-y-* the
    parent had. `flex` makes each toggle its own row so normal vertical
    spacing between fields actually applies.
--}}
<label for="{{ $id }}" class="flex w-fit cursor-pointer items-center gap-3 text-sm text-ink has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50">
    <span class="relative inline-flex h-7 w-12 shrink-0 items-center">
        <input
            id="{{ $id }}"
            type="checkbox"
            role="switch"
            value="1"
            @if ($name) name="{{ $name }}" @endif
            @if ($checked) checked @endif
            {{ $attributes->merge(['class' => 'peer sr-only']) }}
        >
        <span class="pointer-events-none absolute inset-0 rounded-full bg-neutral-300 transition-colors duration-150 ease-smooth peer-checked:bg-primary peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary motion-reduce:transition-none"></span>
        <span class="pointer-events-none relative inline-block h-6 w-6 translate-x-0.5 rounded-full bg-canvas shadow-md transition-transform duration-150 ease-smooth peer-checked:translate-x-[1.375rem] rtl:peer-checked:-translate-x-[1.375rem] motion-reduce:transition-none"></span>
    </span>
    {{ $label }}
</label>
