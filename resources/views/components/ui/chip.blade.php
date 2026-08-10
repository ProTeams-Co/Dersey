@props([
    'removable' => true,
])

{{-- Removal here is purely presentational (removes the chip element from
     the DOM) — there is no filter/business logic behind it in this batch. --}}
<span data-chip {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full bg-surface py-1 ps-3 text-sm text-ink ' . ($removable ? 'pe-1.5' : 'pe-3')]) }}>
    {{ $slot }}

    @if ($removable)
        <button
            type="button"
            data-chip-remove
            aria-label="{{ __('common.remove') }}"
            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-muted transition-colors duration-150 ease-smooth hover:bg-neutral-300 hover:text-ink motion-reduce:transition-none"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true">
                <path d="M18 6 6 18" /><path d="m6 6 12 12" />
            </svg>
        </button>
    @endif
</span>

@once
    @push('scripts')
        <script type="module">
            $(document).on('click', '[data-chip-remove]', function () {
                $(this).closest('[data-chip]').remove();
            });
        </script>
    @endpush
@endonce
