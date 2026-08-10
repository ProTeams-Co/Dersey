@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'checked' => false,
])

@php
    $id = $id ?? 'field-'.\Illuminate\Support\Str::random(8);
@endphp

<label for="{{ $id }}" class="inline-flex cursor-pointer items-center gap-2 text-sm text-ink has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50">
    <span class="relative inline-flex h-4 w-4 shrink-0">
        <input
            id="{{ $id }}"
            type="radio"
            @if ($name) name="{{ $name }}" @endif
            @if ($checked) checked @endif
            {{ $attributes->merge([
                'class' => 'peer h-4 w-4 shrink-0 appearance-none rounded-full border border-interactive bg-canvas transition-colors duration-150 ease-smooth checked:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-canvas motion-reduce:transition-none',
            ]) }}
        >
        <span class="pointer-events-none absolute inset-1 scale-0 rounded-full bg-primary transition-transform duration-150 ease-smooth peer-checked:scale-100 motion-reduce:transition-none"></span>
    </span>
    {{ $label }}
</label>
