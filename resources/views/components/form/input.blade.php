@props([
    'name' => null,
    'label' => null,
    'type' => 'text',
    'id' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

@php
    $id = $id ?? 'field-'.\Illuminate\Support\Str::random(8);
    $hintId = $hint ? $id.'-hint' : null;
    $errorId = $error ? $id.'-error' : null;
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-ink">
            {{ $label }}
            @if ($required)
                <span class="text-danger" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <input
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        type="{{ $type }}"
        @if ($required) required @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->merge([
            'class' => 'w-full rounded-lg border bg-canvas px-3.5 py-2.5 text-sm text-ink placeholder:text-muted transition-colors duration-150 ease-smooth focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-canvas disabled:cursor-not-allowed disabled:bg-surface disabled:text-muted motion-reduce:transition-none '
                . ($error ? 'border-danger' : 'border-interactive'),
        ]) }}
    >

    @if ($hint && ! $error)
        <p id="{{ $hintId }}" class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $errorId }}" class="mt-1.5 text-xs text-danger">{{ $error }}</p>
    @endif
</div>
