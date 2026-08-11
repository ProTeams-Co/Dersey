import $ from 'jquery';

/**
 * licenseKey: 'GPL' is required from CKEditor 5 v44 onward even for the
 * free/open-source build - omitting it degrades the editor into a
 * read-only "evaluation" state instead of failing loudly, so this is not
 * optional boilerplate.
 *
 * CKEditor 5 (~600 kB) is only ever dynamically imported when a
 * [data-editor] element actually exists on the page - eagerly bundling it
 * into admin.js would ship that weight to every admin page, including
 * ones with no rich-text field at all (same reasoning as media.js's
 * FilePond, and CLAUDE.md §13's GSAP/Lenis rule for the storefront).
 *
 * The upload adapter reuses the same temporary upload endpoint as
 * admin/media.js (admin.media.store, real MIME-sniffed validation
 * server-side) - no resource persists editor-uploaded images past that
 * temp storage yet, same "infrastructure only" reasoning as media.js.
 */

function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

class TempUploadAdapter {
    constructor(loader, uploadUrl) {
        this.loader = loader;
        this.uploadUrl = uploadUrl;
    }

    upload() {
        return this.loader.file.then(
            (file) =>
                new Promise((resolve, reject) => {
                    const formData = new FormData();
                    formData.append('file', file);

                    $.ajax({
                        url: this.uploadUrl,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: { 'X-CSRF-TOKEN': csrfToken() },
                    })
                        .done((response) => resolve({ default: response.url }))
                        .fail((xhr) => reject(xhr.responseJSON?.message || 'Upload failed'));
                })
        );
    }

    abort() {
        //
    }
}

async function initEditor(el) {
    const $el = $(el);
    const uploadUrl = $el.data('editorUploadUrl');

    const [ckeditor] = await Promise.all([import('ckeditor5'), import('ckeditor5/ckeditor5.css')]);
    const { ClassicEditor, Essentials, Paragraph, Bold, Italic, Link, List, Heading, BlockQuote, Image, ImageUpload, ImageToolbar, ImageStyle } = ckeditor;

    const editor = await ClassicEditor.create(el, {
        licenseKey: 'GPL',
        language: 'ar',
        plugins: [Essentials, Paragraph, Bold, Italic, Link, List, Heading, BlockQuote, Image, ImageUpload, ImageToolbar, ImageStyle],
        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'uploadImage', 'undo', 'redo'],
        image: {
            toolbar: ['imageStyle:inline', 'imageStyle:block', 'imageStyle:side'],
        },
    });

    if (uploadUrl) {
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => new TempUploadAdapter(loader, uploadUrl);
    }

    $el.data('ckeditor-instance', editor);
}

function init() {
    document.querySelectorAll('[data-editor]').forEach(initEditor);
}

export default { init };
