{{--
    Visual mount point + config for admin/media.js's FilePond instance -
    this component renders no preview markup itself (FilePond builds its
    own DOM around the input once JS initializes it); without JS, it's a
    plain multi/single file input, degrading gracefully rather than
    disappearing.

    Uploads go to a temporary endpoint (admin.media.store/destroy, real
    MIME-sniffed validation - see MediaUploadController), not directly to
    Cloudflare R2 - CLAUDE.md's "not the original, always a conversion"
    rule applies once a real resource (products, banners, ...) actually
    attaches this to a model, which is out of this batch's scope.
--}}
@props([
    'name',
    'multiple' => false,
    'maxFiles' => null,
    'existing' => [],
    'label' => null,
    'hint' => null,
])

<div
    data-media-picker
    data-media-upload-url="{{ route('admin.media.store') }}"
    data-media-revert-url="{{ route('admin.media.destroy', ['file' => '__ID__']) }}"
    data-media-multiple="{{ $multiple ? '1' : '0' }}"
    @if ($maxFiles) data-media-max="{{ $maxFiles }}" @endif
>
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-ink">{{ $label }}</label>
    @endif

    <input
        type="file"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        @if ($multiple) multiple @endif
        accept="image/png,image/jpeg,image/webp"
        data-media-input
    >

    @foreach ($existing as $file)
        <input type="hidden" name="{{ $name }}_existing[]" value="{{ is_array($file) ? $file['id'] : $file }}">
    @endforeach

    @if ($hint)
        <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
    @endif
</div>
