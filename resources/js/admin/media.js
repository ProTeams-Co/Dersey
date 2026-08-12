import $ from 'jquery';

/**
 * FilePond wired to a TEMPORARY upload endpoint (admin.media.store/destroy,
 * real MIME-sniffed validation server-side - see MediaUploadController),
 * not Cloudflare R2 directly - no resource actually attaches uploaded
 * media to a model yet (that's 3.1+), so there is nothing further to wire
 * up here than "upload now, revert removes it".
 *
 * FilePond + its plugins (~130 kB) are only ever dynamically imported when
 * a [data-media-picker] actually exists on the page - eagerly bundling
 * them into admin.js would ship that weight to every admin page,
 * including ones with no file picker at all (same reasoning as CLAUDE.md
 * §13's GSAP/Lenis dynamic-import rule for the storefront).
 *
 * RTL: no FilePond-specific option needed - its CSS already follows the
 * inherited `dir` from <html dir="rtl"> (the admin panel is fixed RTL, see
 * CLAUDE.md), confirmed by using it on this project's RTL-only admin pages.
 */

function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

async function initPicker(el) {
    const $el = $(el);
    const $input = $el.find('[data-media-input]');

    if (!$input.length) return;

    // `filepond` itself has no default export (only named exports like
    // create()/registerPlugin() - confirmed by reading its ESM build), so
    // this one is the whole module namespace object, not { default: ... }
    // like the three plugins below (which each do `export default plugin`).
    const [
        FilePond,
        { default: FilePondPluginImagePreview },
        { default: FilePondPluginImageCrop },
        { default: FilePondPluginFileValidateType },
    ] = await Promise.all([
        import('filepond'),
        import('filepond-plugin-image-preview'),
        import('filepond-plugin-image-crop'),
        import('filepond-plugin-file-validate-type'),
        import('filepond/dist/filepond.min.css'),
        import('filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css'),
    ]);

    FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginImageCrop, FilePondPluginFileValidateType);

    const uploadUrl = $el.data('mediaUploadUrl');
    const revertUrlTemplate = $el.data('mediaRevertUrl');
    const multiple = String($el.data('mediaMultiple')) === '1';
    const maxFiles = $el.data('mediaMax') || null;

    FilePond.create($input.get(0), {
        allowMultiple: multiple,
        maxFiles,
        acceptedFileTypes: ['image/png', 'image/jpeg', 'image/webp'],
        credits: false,
        imagePreviewHeight: 140,
        server: {
            process: {
                url: uploadUrl,
                // Accept: application/json - without it, a validation
                // failure (wrong type, too large, ...) makes Laravel render
                // its HTML error page instead of a JSON body, and onload's
                // JSON.parse() below throws a confusing "Unexpected token
                // '<'" instead of surfacing the actual validation message.
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
                // MediaUploadController returns JSON ({id, url}), but
                // FilePond's default expects the response body itself to
                // be the plain server id - this pulls just the id out so
                // that's what ends up as the field's submitted value.
                onload: (response) => JSON.parse(response).id,
            },
            revert: (uniqueFileId, load, error) => {
                $.ajax({
                    url: revertUrlTemplate.replace('__ID__', uniqueFileId),
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
                })
                    .done(() => load())
                    .fail(() => error('revert failed'));
            },
            load: null,
        },
    });
}

function init() {
    document.querySelectorAll('[data-media-picker]').forEach(initPicker);
}

export default { init };
