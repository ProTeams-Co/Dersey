@props([
    'items' => [],
])

{{-- Native <details>/<summary> — real disclosure semantics and keyboard
     support (Enter/Space toggles a focused summary) for free, no JS. --}}
<div {{ $attributes->merge(['class' => 'divide-y divide-line rounded-xl border border-line']) }}>
    @foreach ($items as $item)
        <details class="group/accordion p-4">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-medium text-ink [&::-webkit-details-marker]:hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                {{ $item['title'] }}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 transition-transform duration-150 ease-smooth group-open/accordion:rotate-180 motion-reduce:transition-none" aria-hidden="true">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </summary>
            <div class="mt-3 text-sm text-muted">
                {{ $item['content'] }}
            </div>
        </details>
    @endforeach
</div>
