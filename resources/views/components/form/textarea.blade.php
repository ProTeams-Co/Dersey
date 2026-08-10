@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'maxlength' => null,
    'rows' => 4,
])

@php
    $id = $id ?? 'field-'.\Illuminate\Support\Str::random(8);
    $hintId = $hint ? $id.'-hint' : null;
    $errorId = $error ? $id.'-error' : null;
    $counterId = $maxlength ? $id.'-count' : null;
    $describedBy = collect([$errorId, $hintId, $counterId])->filter()->implode(' ') ?: null;
    $value = $attributes->get('value', '');
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

    <textarea
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        rows="{{ $rows }}"
        @if ($maxlength) maxlength="{{ $maxlength }}" data-char-counter="{{ $counterId }}" @endif
        @if ($required) required @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('value')->merge([
            'class' => 'w-full resize-y rounded-lg border bg-canvas px-3.5 py-2.5 text-sm text-ink placeholder:text-muted transition-colors duration-150 ease-smooth focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-canvas disabled:cursor-not-allowed disabled:bg-surface disabled:text-muted motion-reduce:transition-none '
                . ($error ? 'border-danger' : 'border-interactive'),
        ]) }}
    >{{ $value }}</textarea>

    <div class="mt-1.5 flex items-start justify-between gap-4">
        <div>
            @if ($hint && ! $error)
                <p id="{{ $hintId }}" class="text-xs text-muted">{{ $hint }}</p>
            @endif

            @if ($error)
                <p id="{{ $errorId }}" class="text-xs text-danger">{{ $error }}</p>
            @endif
        </div>

        @if ($maxlength)
            <p id="{{ $counterId }}" class="shrink-0 text-xs text-muted">
                {{ __('components.form.char_count', ['current' => \Illuminate\Support\Str::length($value), 'max' => $maxlength]) }}
            </p>
        @endif
    </div>
</div>

@once
    @push('scripts')
        <script type="module">
            $(document).on('input', '[data-char-counter]', function () {
                var $counter = $('#' + $(this).data('charCounter'));
                if (!$counter.length) return;
                $counter.text(this.value.length + ' / ' + $(this).attr('maxlength'));
            });
        </script>
    @endpush
@endonce
