@props([
    'type' => 'info',
    'closable' => false,
])

@php
    /**
     * Tinted (10% opacity) backgrounds, never solid semantic fills — sidesteps
     * the accent/warning-foreground=ink gotcha entirely (text stays text-ink
     * throughout, readable against every tint) instead of juggling a
     * per-variant foreground color.
     */
    $variants = [
        'info' => ['bg' => 'bg-accent/10', 'border' => 'border-accent/20', 'icon' => 'text-accent-700'],
        'success' => ['bg' => 'bg-success/10', 'border' => 'border-success/20', 'icon' => 'text-success-700'],
        'warning' => ['bg' => 'bg-warning/10', 'border' => 'border-warning/20', 'icon' => 'text-warning-700'],
        'danger' => ['bg' => 'bg-danger/10', 'border' => 'border-danger/20', 'icon' => 'text-danger-700'],
    ];

    $icons = [
        'info' => '<circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />',
        'success' => '<path d="M21.801 10A10 10 0 1 1 17 3.335" /><path d="m9 11 3 3L22 4" />',
        'warning' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" /><path d="M12 9v4" /><path d="M12 17h.01" />',
        'danger' => '<circle cx="12" cy="12" r="10" /><line x1="12" x2="12" y1="8" y2="12" /><line x1="12" x2="12.01" y1="16" y2="16" />',
    ];

    $style = $variants[$type] ?? $variants['info'];
    $icon = $icons[$type] ?? $icons['info'];
@endphp

<div
    data-alert
    role="alert"
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border p-4 ' . $style['bg'] . ' ' . $style['border']]) }}
>
    <span class="{{ $style['icon'] }} mt-0.5 shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
            {!! $icon !!}
        </svg>
    </span>

    <div class="min-w-0 flex-1 text-sm text-ink">
        {{ $slot }}
    </div>

    @if ($closable)
        <button
            type="button"
            data-alert-dismiss
            aria-label="{{ __('common.close') }}"
            class="-me-1 -mt-1 shrink-0 rounded-md p-1 text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                <path d="M18 6 6 18" /><path d="m6 6 12 12" />
            </svg>
        </button>
    @endif
</div>

@once
    @push('scripts')
        <script type="module">
            $(document).on('click', '[data-alert-dismiss]', function () {
                $(this).closest('[data-alert]').remove();
            });
        </script>
    @endpush
@endonce
