@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'checked' => false,
    'indeterminate' => false,
])

@php
    $id = $id ?? 'field-'.\Illuminate\Support\Str::random(8);
@endphp

{{--
    appearance-none + hand-drawn check/dash icons, not accent-color — the
    native unchecked appearance has no reliable cross-browser way to apply
    the required interactive-border token to it, and this component's
    border is a hard requirement (see this batch's mandatory border color
    rule for form components). indeterminate has no HTML attribute
    (browsers only expose it as a JS DOM
    property), so a real checkbox in that state always needs one script line
    to set it — the CSS response to that state (below) is pure CSS either way.
--}}
<label for="{{ $id }}" class="inline-flex cursor-pointer items-center gap-2 text-sm text-ink has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50">
    <span class="relative inline-flex h-4 w-4 shrink-0">
        <input
            id="{{ $id }}"
            type="checkbox"
            @if ($name) name="{{ $name }}" @endif
            @if ($checked) checked @endif
            @if ($indeterminate) data-indeterminate @endif
            {{ $attributes->merge([
                'class' => 'peer h-4 w-4 shrink-0 appearance-none rounded border border-interactive bg-canvas transition-colors duration-150 ease-smooth checked:border-primary checked:bg-primary indeterminate:border-primary indeterminate:bg-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-canvas motion-reduce:transition-none',
            ]) }}
        >
        <svg class="pointer-events-none absolute inset-0 h-4 w-4 stroke-primary-foreground opacity-0 peer-checked:opacity-100 peer-indeterminate:opacity-0" viewBox="0 0 16 16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3.5 8.5L6.5 11.5L12.5 5" />
        </svg>
        <svg class="pointer-events-none absolute inset-0 h-4 w-4 stroke-primary-foreground opacity-0 peer-indeterminate:opacity-100" viewBox="0 0 16 16" fill="none" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M4 8h8" />
        </svg>
    </span>
    {{ $label }}
</label>

@if ($indeterminate)
    @push('scripts')
        <script type="module">
            document.getElementById('{{ $id }}').indeterminate = true;
        </script>
    @endpush
@endif
