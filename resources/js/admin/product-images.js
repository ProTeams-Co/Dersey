import $ from 'jquery';
import Ajax from '../core/ajax';
import Toast from '../core/toast';

/**
 * Batch 3.2-C - the image gallery tab's own interactivity. Lazy-loaded the
 * first time that tab opens (see product-form.js), same reasoning as
 * product-variants.js: a meaningfully large chunk of behavior every OTHER
 * product tab would otherwise pay for loading even when never opened.
 *
 * Uploads go through a SEPARATE FilePond instance from admin/media.js's own
 * (not reused, not modified) - it hits the exact same shared temporary
 * endpoints (admin.media.store/destroy), but this file is the one that
 * understands what to do with a finished temp upload: show a small pending
 * card (color + alt), and on save, call ProductImagesController::store()
 * to actually link it to this product.
 *
 * SECURITY: like product-variants.js, only ever writes user-controlled
 * text (filenames, alt text) into the DOM via jQuery .text()/.val(), never
 * .html() - see core/ajax.js's own module docblock.
 */

const ALT_SAVE_DEBOUNCE_MS = 500;

function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

function updateCounter($root) {
    const count = $root.find('[data-image-card]').length;
    const max = Number($root.data('maxImages'));

    $root.find('[data-image-counter]').text(Ajax.lang('admin_image_counter', { count, max }));
    $root.find('[data-image-upload-input]').prop('disabled', count >= max);
    $root.find('[data-image-empty-message]').prop('hidden', count > 0);
    $root.find('[data-image-save-order-wrap]').prop('hidden', count === 0);
}

function buildPendingCard($root, tempId, filename) {
    const optionsHtml = $root.find('[data-image-color-options-template]').get(0)?.innerHTML ?? '';

    const $card = $('<div>', {
        'data-image-pending-card': '',
        class: 'space-y-2 rounded-xl border border-dashed border-line p-3',
    });

    $card.append($('<p>', { class: 'text-xs font-medium text-muted', text: $root.data('pendingLabel') || '' }));
    $card.append($('<p>', { class: 'truncate text-xs text-muted', text: filename }));

    const $colorSelect = $('<select>', {
        'data-image-color-select': '',
        class: 'w-full rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink',
    }).html(optionsHtml);
    $card.append($colorSelect);

    const $altAr = $('<input>', {
        type: 'text',
        'data-image-alt-ar': '',
        dir: 'rtl',
        class: 'w-full rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink',
    });
    const $altEn = $('<input>', {
        type: 'text',
        'data-image-alt-en': '',
        dir: 'ltr',
        class: 'w-full rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink',
    });
    $card.append($altAr, $altEn);

    const $saveButton = $('<button>', {
        type: 'button',
        'data-image-link-button': '',
        class: 'w-full rounded-lg bg-primary px-2 py-1 text-xs font-medium text-primary-foreground hover:bg-primary/90',
        text: $root.data('linkLabel') || '',
    });
    $card.data('tempId', tempId);
    $card.append($saveButton);

    return $card;
}

async function initUploader($root) {
    const [FilePond, { default: FilePondPluginImagePreview }, { default: FilePondPluginFileValidateType }] = await Promise.all([
        import('filepond'),
        import('filepond-plugin-image-preview'),
        import('filepond-plugin-file-validate-type'),
        import('filepond/dist/filepond.min.css'),
        import('filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css'),
    ]);

    FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateType);

    const input = $root.find('[data-image-upload-input]').get(0);
    if (!input) return;

    const pond = FilePond.create(input, {
        allowMultiple: true,
        acceptedFileTypes: ['image/png', 'image/jpeg', 'image/webp'],
        credits: false,
        imagePreviewHeight: 120,
        // Client-side convenience only - ProductImagesController::store()
        // is the real, authoritative enforcement of MAX_IMAGES (this
        // check can't see pending-but-not-yet-linked cards, only cards
        // already in the grid).
        beforeAddFile: () => {
            const atLimit = $root.find('[data-image-card]').length >= Number($root.data('maxImages'));

            if (atLimit) Toast.warning(Ajax.lang('admin_image_limit_reached'));

            return !atLimit;
        },
        server: {
            process: {
                url: $root.data('mediaUploadUrl'),
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
                onload: (response) => JSON.parse(response).id,
            },
            revert: (uniqueFileId, load, error) => {
                $.ajax({
                    url: $root.data('mediaRevertUrl').replace('__ID__', uniqueFileId),
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
                })
                    .done(() => load())
                    .fail(() => error('revert failed'));
            },
            load: null,
        },
        onprocessfile: (error, file) => {
            if (error) {
                Toast.error(Ajax.lang('admin_image_upload_failed'));
                return;
            }

            const $card = buildPendingCard($root, file.serverId, file.filename);
            $card.data('pondFileId', file.id);
            $card.data('pond', pond);
            $root.find('[data-image-pending-list]').append($card);
        },
    });
}

function initPendingCardSave($root) {
    $root.on('click', '[data-image-link-button]', function () {
        const $card = $(this).closest('[data-image-pending-card]');
        const tempId = $card.data('tempId');

        Ajax.request({
            url: $root.data('storeUrl'),
            method: 'POST',
            data: {
                temp_id: tempId,
                color_value_id: $card.find('[data-image-color-select]').val() || null,
                alt: {
                    ar: $card.find('[data-image-alt-ar]').val(),
                    en: $card.find('[data-image-alt-en]').val(),
                },
            },
            $trigger: $(this),
        })
            .done((response) => {
                Toast.success(response.message || '');

                const pond = $card.data('pond');
                const pondFileId = $card.data('pondFileId');
                if (pond && pondFileId) pond.removeFile(pondFileId);

                $card.remove();
                $root.find('[data-image-grid]').append(buildCard(response.image, $root));
                updateCounter($root);
            })
            .fail((xhr) => {
                if (xhr.status === 422) {
                    const message = xhr.responseJSON?.errors?.temp_id?.[0] || xhr.responseJSON?.message || '';
                    Toast.error(message);
                }
            });
    });
}

/**
 * Mirrors admin/products/_image-card.blade.php's markup + data attributes
 * exactly - a card built here (right after linking) and one rendered
 * server-side on the next page load must be handled identically by every
 * other handler below (reorder, edit, primary, delete).
 */
function buildCard(image, $root) {
    const optionsHtml = $root.find('[data-image-color-options-template]').get(0)?.innerHTML ?? '';

    const $card = $('<div>', {
        'data-image-card': '',
        'data-image-id': image.id,
        class: 'space-y-2 rounded-xl border border-line p-3',
    });

    const $top = $('<div>', { class: 'flex items-center justify-between' });
    $top.append($('<span>', { 'data-image-drag-handle': '', class: 'cursor-grab text-muted', text: '⠿' }));
    $top.append(
        $('<button>', {
            type: 'button',
            'data-image-set-primary': '',
            class: 'text-xs font-medium text-muted hover:text-ink',
            text: $root.data('setPrimaryLabel') || '',
        })
    );
    $card.append($top);

    $card.append(
        $('<img>', {
            src: image.url,
            width: image.width,
            height: image.height,
            style: `aspect-ratio: ${image.width} / ${image.height}`,
            class: 'w-full rounded-lg object-cover',
            alt: image.alt?.ar || '',
        })
    );

    const $colorSelect = $('<select>', {
        'data-image-color-select': '',
        class: 'w-full rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink',
    }).html(optionsHtml);
    if (image.color_value_id) $colorSelect.val(String(image.color_value_id));
    $card.append($colorSelect);

    $card.append(
        $('<input>', { type: 'text', 'data-image-alt-ar': '', dir: 'rtl', value: image.alt?.ar || '', class: 'w-full rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink' }),
        $('<input>', { type: 'text', 'data-image-alt-en': '', dir: 'ltr', value: image.alt?.en || '', class: 'w-full rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink' }),
        $('<button>', {
            type: 'button',
            'data-image-delete': '',
            class: 'w-full rounded-lg border border-danger/30 px-2 py-1 text-xs font-medium text-danger hover:bg-danger/10',
            text: $root.data('deleteLabel') || '',
        })
    );

    return $card;
}

function initSortable($root) {
    import('sortablejs').then(({ default: Sortable }) => {
        const list = $root.find('[data-image-grid]').get(0);
        if (!list) return;

        Sortable.create(list, {
            handle: '[data-image-drag-handle]',
            animation: 150,
        });
    });
}

function initSaveOrder($root) {
    $root.on('click', '[data-image-save-order-button]', function () {
        const images = $root
            .find('[data-image-card]')
            .map(function (index) {
                return { id: $(this).data('imageId'), sort: index };
            })
            .get();

        if (images.length === 0) return;

        Ajax.request({
            url: $root.data('reorderUrl'),
            method: 'PATCH',
            data: { images },
            $trigger: $(this),
        }).done((response) => {
            Toast.success(response.message || '');
        });
    });
}

function updateUrl($root, imageId, template) {
    return $root.data(template).replace('__ID__', imageId);
}

function initDelete($root) {
    $root.on('click', '[data-image-delete]', function () {
        const $card = $(this).closest('[data-image-card]');
        const imageId = $card.data('imageId');

        window.Dersey?.confirmAction?.(Ajax.lang('admin_image_delete_confirm'), () => {
            Ajax.request({
                url: updateUrl($root, imageId, 'destroyUrlTemplate'),
                method: 'DELETE',
                $trigger: $(this),
            }).done((response) => {
                Toast.success(response.message || '');
                $card.remove();
                updateCounter($root);
            });
        });
    });
}

function initSetPrimary($root) {
    $root.on('click', '[data-image-set-primary]', function () {
        const $card = $(this).closest('[data-image-card]');
        const imageId = $card.data('imageId');

        Ajax.request({
            url: updateUrl($root, imageId, 'primaryUrlTemplate'),
            method: 'POST',
            $trigger: $(this),
        }).done((response) => {
            Toast.success(response.message || '');
            window.location.reload();
        });
    });
}

function initMetaAutosave($root) {
    $root.on('input change', '[data-image-card] [data-image-color-select], [data-image-card] [data-image-alt-ar], [data-image-card] [data-image-alt-en]', function () {
        const $card = $(this).closest('[data-image-card]');
        const imageId = $card.data('imageId');

        // Timer lives on the card itself, not a shared module-level
        // variable - editing card A then quickly editing card B within
        // the debounce window must not cancel card A's pending save.
        clearTimeout($card.data('autosaveTimer'));

        const timer = setTimeout(() => {
            Ajax.request({
                url: updateUrl($root, imageId, 'updateUrlTemplate'),
                method: 'PUT',
                data: {
                    color_value_id: $card.find('[data-image-color-select]').val() || null,
                    alt: {
                        ar: $card.find('[data-image-alt-ar]').val(),
                        en: $card.find('[data-image-alt-en]').val(),
                    },
                },
            }).done((response) => {
                Toast.success(response.message || '');
            });
        }, ALT_SAVE_DEBOUNCE_MS);

        $card.data('autosaveTimer', timer);
    });
}

function init(el) {
    const $root = $(el);
    if (!$root.length) return;

    initUploader($root);
    initPendingCardSave($root);
    initSortable($root);
    initSaveOrder($root);
    initDelete($root);
    initSetPrimary($root);
    initMetaAutosave($root);
    updateCounter($root);
}

export default { init };
