{{--
    A generic label/hint/error wrapper for form content that isn't one of
    the x-form.* inputs (which already bundle their own label/hint/error) -
    for wrapping x-admin.media-picker, x-admin.repeater, or any custom
    control that needs the same chrome around it.
--}}
@props(['label' => null, 'hint' => null, 'error' => null, 'required' => false, 'for' => null])

<div>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="mb-1.5 block text-sm font-medium text-ink">
            {{ $label }}
            @if ($required)
                <span class="text-danger" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint && ! $error)
        <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="mt-1.5 text-xs text-danger">{{ $error }}</p>
    @endif
</div>
