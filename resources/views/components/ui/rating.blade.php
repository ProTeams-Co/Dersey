@props([
    'value' => 0,
    'max' => 5,
    'readonly' => true,
    'name' => null,
    'size' => 'md',
])

@php
    $sizes = ['sm' => 'h-3.5 w-3.5', 'md' => 'h-5 w-5', 'lg' => 'h-6 w-6'];
    $starClass = $sizes[$size] ?? $sizes['md'];
    $starPath = 'M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z';
@endphp

@if ($readonly)
    {{-- Fill width is snapped to the nearest half-star (w-0 / w-1/2 /
         w-full), not an inline style percentage — no arbitrary CSS this
         batch, and it covers the realistic case (half-star increments). --}}
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} role="img" aria-label="{{ __('components.rating.label', ['rating' => $value]) }}">
        @for ($i = 1; $i <= $max; $i++)
            @php
                $fillFraction = min(1, max(0, $value - ($i - 1)));
                $fillClass = $fillFraction >= 1 ? 'w-full' : ($fillFraction >= 0.5 ? 'w-1/2' : 'w-0');
            @endphp
            <span class="relative inline-block {{ $starClass }}" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" class="{{ $starClass }} text-neutral-300"><path d="{{ $starPath }}" /></svg>
                <span class="absolute inset-y-0 start-0 overflow-hidden {{ $fillClass }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="{{ $starClass }} text-warning-600"><path d="{{ $starPath }}" /></svg>
                </span>
            </span>
        @endfor
    </div>
@else
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} role="radiogroup" aria-label="{{ __('components.rating.label', ['rating' => $value]) }}">
        @for ($i = 1; $i <= $max; $i++)
            <label class="{{ $starClass }} cursor-pointer">
                <input
                    type="radio"
                    @if ($name) name="{{ $name }}" @endif
                    value="{{ $i }}"
                    @if ((int) $value === $i) checked @endif
                    class="peer sr-only"
                    aria-label="{{ __('components.rating.set_rating', ['rating' => $i]) }}"
                >
                <svg viewBox="0 0 24 24" fill="currentColor" class="{{ $starClass }} text-neutral-300 transition-colors duration-150 ease-smooth peer-checked:text-warning-600 motion-reduce:transition-none" aria-hidden="true">
                    <path d="{{ $starPath }}" />
                </svg>
            </label>
        @endfor
    </div>
@endif
