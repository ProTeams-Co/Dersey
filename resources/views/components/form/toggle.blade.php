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
--}}
<label for="{{ $id }}" class="inline-flex cursor-pointer items-center gap-2 text-sm text-ink has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50">
    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
        <input
            id="{{ $id }}"
            type="checkbox"
            role="switch"
            @if ($name) name="{{ $name }}" @endif
            @if ($checked) checked @endif
            {{ $attributes->merge(['class' => 'peer sr-only']) }}
        >
        <span class="pointer-events-none absolute inset-0 rounded-full bg-neutral-300 transition-colors duration-150 ease-smooth peer-checked:bg-primary peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary motion-reduce:transition-none"></span>
        <span class="pointer-events-none relative inline-block h-5 w-5 translate-x-0.5 rounded-full bg-canvas shadow-sm transition-transform duration-150 ease-smooth peer-checked:translate-x-[1.375rem] rtl:peer-checked:-translate-x-[1.375rem] motion-reduce:transition-none"></span>
    </span>
    {{ $label }}
</label>
