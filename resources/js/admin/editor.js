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
    // Batch 3.2-A: a translatable-tabs description field needs the SAME
    // editor instance's admin-facing toolbar in Arabic (this panel is
    // Arabic-only, CLAUDE.md) but its CONTENT direction/language must
    // follow whichever locale tab it belongs to - an English description
    // typed into an RTL-content editor reads backwards. Defaults to 'ar'
    // (CKEditor 5's `language` config also accepts this {ui, content}
    // object form, not just a bare string) so every other [data-editor]
    // usage that never sets this attribute keeps behaving exactly as
    // before.
    const contentLang = $el.data('editorContentLang') || 'ar';

    const [ckeditor] = await Promise.all([import('ckeditor5'), import('ckeditor5/ckeditor5.css')]);
    const { ClassicEditor, Essentials, Paragraph, Bold, Italic, Link, List, Heading, BlockQuote, Image, ImageUpload, ImageToolbar, ImageStyle } = ckeditor;

    const editor = await ClassicEditor.create(el, {
        licenseKey: 'GPL',
        language: { ui: 'ar', content: contentLang },
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

// initEditor exported (init()'s own [data-editor] bulk-scan behavior is
// completely unchanged) so a caller that needs it deferred past page load -
// admin/product-form.js's translations tab, which must not import CKEditor
// at all until that tab is actually opened, not just "not in the base
// bundle" - can initialize one element on demand instead of relying on the
// bulk scan finding it already in the DOM at load time.
export default { init, initEditor };
