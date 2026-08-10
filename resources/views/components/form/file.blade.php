@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'accept' => null,
    'multiple' => false,
])

@php
    $id = $id ?? 'field-'.\Illuminate\Support\Str::random(8);
@endphp

{{-- Visual shape only, per this batch's scope — no drag/drop handling, no
     upload/preview logic. The hidden input is still a real, keyboard- and
     click-reachable file input; only the styling is custom. --}}
<div>
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-ink">{{ $label }}</label>
    @endif

    <label
        for="{{ $id }}"
        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-interactive bg-canvas px-6 py-10 text-center transition-colors duration-150 ease-smooth hover:bg-surface has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary has-[:focus-visible]:ring-offset-2 has-[:focus-visible]:ring-offset-canvas motion-reduce:transition-none"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-muted" aria-hidden="true">
            <path d="M12 15V3m0 0 4 4m-4-4L8 7" />
            <path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4" />
        </svg>

        <span class="text-sm text-ink">{{ __('components.form.file_drop_instruction') }}</span>
        <span class="text-xs text-muted underline underline-offset-2">{{ __('components.form.file_browse') }}</span>

        <input
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            type="file"
            @if ($accept) accept="{{ $accept }}" @endif
            @if ($multiple) multiple @endif
            @if ($error) aria-invalid="true" @endif
            {{ $attributes->merge(['class' => 'sr-only']) }}
        >
    </label>

    @if ($hint && ! $error)
        <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="mt-1.5 text-xs text-danger">{{ $error }}</p>
    @endif
</div>
