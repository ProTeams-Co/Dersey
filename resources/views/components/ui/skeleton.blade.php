@props([
    'type' => 'text',
    'lines' => 3,
])

@if ($type === 'text')
    <div {{ $attributes->merge(['class' => 'space-y-2']) }} aria-hidden="true">
        @for ($i = 0; $i < $lines; $i++)
            <div class="h-3 animate-pulse rounded bg-neutral-200 motion-reduce:animate-none {{ $i === $lines - 1 ? 'w-3/4' : 'w-full' }}"></div>
        @endfor
    </div>
@elseif ($type === 'card')
    <div {{ $attributes->merge(['class' => 'space-y-3']) }} aria-hidden="true">
        <div class="aspect-square w-full animate-pulse rounded-lg bg-neutral-200 motion-reduce:animate-none"></div>
        <div class="h-3 w-3/4 animate-pulse rounded bg-neutral-200 motion-reduce:animate-none"></div>
        <div class="h-3 w-1/2 animate-pulse rounded bg-neutral-200 motion-reduce:animate-none"></div>
    </div>
@else
    {{-- image --}}
    <div {{ $attributes->merge(['class' => 'animate-pulse rounded-lg bg-neutral-200 motion-reduce:animate-none']) }} aria-hidden="true"></div>
@endif
