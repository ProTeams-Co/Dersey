{{--
    data-ajax-form is core/form.js's own markup contract (Batch 1.4) - this
    just adds CSRF, PUT/PATCH/DELETE method-spoofing, and (when
    translatable) the data-translatable-form marker admin/form.js looks
    for to open the right x-admin.translatable-tabs tab on a 422 that
    targets a field in a non-active language.
--}}
@props(['action', 'method' => 'POST', 'translatable' => false])

@php
    $spoofMethod = in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE'], true) ? strtoupper($method) : null;
@endphp

<form
    method="POST"
    action="{{ $action }}"
    data-ajax-form
    @if ($translatable) data-translatable-form @endif
    {{ $attributes }}
>
    @csrf
    @if ($spoofMethod)
        @method($spoofMethod)
    @endif

    {{ $slot }}
</form>
