<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-line bg-surface']) }}>
    @isset($header)
        <div class="border-b border-line p-4">
            {{ $header }}
        </div>
    @endisset

    <div class="p-4">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-line p-4">
            {{ $footer }}
        </div>
    @endisset
</div>
