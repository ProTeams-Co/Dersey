@props([
    'name' => null,
    'id' => null,
    'value' => 1,
    'min' => 1,
    'max' => 99,
])

@php
    $id = $id ?? 'field-'.\Illuminate\Support\Str::random(8);
@endphp

<div data-quantity data-min="{{ $min }}" data-max="{{ $max }}" class="inline-flex items-center rounded-lg border border-interactive">
    <button
        type="button"
        data-action="quantity-decrease"
        aria-label="{{ __('components.form.quantity_decrease') }}"
        aria-controls="{{ $id }}"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-s-lg text-ink transition-colors duration-150 ease-smooth hover:bg-surface disabled:cursor-not-allowed disabled:text-muted disabled:hover:bg-transparent motion-reduce:transition-none"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4" aria-hidden="true">
            <path d="M5 12h14" />
        </svg>
    </button>

    <input
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        type="text"
        inputmode="numeric"
        role="spinbutton"
        aria-valuemin="{{ $min }}"
        aria-valuemax="{{ $max }}"
        aria-valuenow="{{ $value }}"
        {{ $attributes->merge([
            'value' => $value,
            'class' => 'h-10 w-12 shrink-0 border-x border-interactive bg-canvas text-center text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary motion-reduce:transition-none',
        ]) }}
    >

    <button
        type="button"
        data-action="quantity-increase"
        aria-label="{{ __('components.form.quantity_increase') }}"
        aria-controls="{{ $id }}"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-e-lg text-ink transition-colors duration-150 ease-smooth hover:bg-surface disabled:cursor-not-allowed disabled:text-muted disabled:hover:bg-transparent motion-reduce:transition-none"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4" aria-hidden="true">
            <path d="M12 5v14M5 12h14" />
        </svg>
    </button>
</div>

@once
    @push('scripts')
        <script type="module">
            $(document).on('click', '[data-action="quantity-decrease"], [data-action="quantity-increase"]', function () {
                var $button = $(this);
                var $wrapper = $button.closest('[data-quantity]');
                var $input = $wrapper.find('input');
                var min = parseInt($wrapper.data('min'), 10);
                var max = parseInt($wrapper.data('max'), 10);
                var current = parseInt($input.val(), 10) || 0;
                var next = $button.data('action') === 'quantity-increase' ? current + 1 : current - 1;
                next = Math.min(max, Math.max(min, next));

                $input.val(next).attr('aria-valuenow', next);
                $wrapper.find('[data-action="quantity-decrease"]').prop('disabled', next <= min);
                $wrapper.find('[data-action="quantity-increase"]').prop('disabled', next >= max);
            });
        </script>
    @endpush
@endonce
