@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'options' => [],
    'placeholder' => null,
])

@php
    $id = $id ?? 'field-'.\Illuminate\Support\Str::random(8);
    $hintId = $hint ? $id.'-hint' : null;
    $errorId = $error ? $id.'-error' : null;
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
    $selected = $attributes->get('value');
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

    <div class="relative">
        <select
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($required) required @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('value')->merge([
                'class' => 'w-full appearance-none rounded-lg border bg-canvas py-2.5 ps-3.5 pe-10 text-sm text-ink transition-colors duration-150 ease-smooth focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-canvas disabled:cursor-not-allowed disabled:bg-surface disabled:text-muted motion-reduce:transition-none '
                    . ($error ? 'border-danger' : 'border-interactive'),
            ]) }}
        >
            @if ($placeholder)
                <option value="" disabled @if (! $selected) selected @endif>{{ $placeholder }}</option>
            @endif

            @foreach ($options as $value => $optionLabel)
                <option value="{{ $value }}" @if ((string) $selected === (string) $value) selected @endif>{{ $optionLabel }}</option>
            @endforeach

            {{ $slot }}
        </select>

        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </div>

    @if ($hint && ! $error)
        <p id="{{ $hintId }}" class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $errorId }}" class="mt-1.5 text-xs text-danger">{{ $error }}</p>
    @endif
</div>
