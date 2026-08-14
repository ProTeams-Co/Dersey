import $ from 'jquery';
import Ajax from '../core/ajax';
import Toast from '../core/toast';

/**
 * Batch 3.2-B - the variant matrix tab's own interactivity. Lazy-loaded
 * the first time that tab opens (see product-form.js), same reasoning as
 * CKEditor/FilePond: this is a meaningfully large chunk of behavior
 * (live preview, inline-editable grid, bulk edit, per-row optimistic-lock
 * version tracking) that every OTHER product tab would otherwise pay for
 * loading even when never opened.
 *
 * SECURITY: Toast messages here are always a static window.Dersey.lang
 * string (via Ajax.lang()) or the server's own translated `message` field
 * (itself always a static translation string from a domain exception's
 * render() - see app/Exceptions) - core/toast.js renders via .innerHTML
 * internally, so admin-authored dynamic text (a blocked/conflicting
 * variant's own option label, e.g. "M / أحمر") is rendered into the page
 * with jQuery .text() instead, never handed to Toast.
 */

const PREVIEW_DEBOUNCE_MS = 400;
const LARGE_GENERATE_CONFIRM_THRESHOLD = 50;

function readSelection($root) {
    const byAttribute = {};

    $root.find('[data-variant-value-checkbox]:checked').each(function () {
        const attributeId = String($(this).data('attributeId'));
        byAttribute[attributeId] = byAttribute[attributeId] || [];
        byAttribute[attributeId].push(Number($(this).val()));
    });

    return byAttribute;
}

function currentAttributeIds($root) {
    const raw = $root.data('currentAttributeIds');
    return String(raw ?? '')
        .split(',')
        .filter((id) => id !== '')
        .map(Number);
}

function newAttributeIds($root, selection) {
    const current = currentAttributeIds($root);
    return Object.keys(selection)
        .map(Number)
        .filter((id) => !current.includes(id) && selection[id].length > 0);
}

function renderDefaultValueSelects($root) {
    const $container = $root.find('[data-variant-default-values]');
    const selection = readSelection($root);
    const newIds = newAttributeIds($root, selection);

    if (newIds.length === 0) {
        $container.empty().prop('hidden', true);
        return;
    }

    $container.empty().prop('hidden', false);

    newIds.forEach((attributeId) => {
        const $group = $root.find(`[data-variant-attribute-group][data-attribute-id="${attributeId}"]`);
        const label = $group.find('p').first().text();

        const $row = $('<div>', { class: 'flex items-center gap-2' });
        $row.append($('<label>', { class: 'text-sm text-ink', text: Ajax.lang('admin_variant_default_value_for', { attribute: label }) }));

        const $select = $('<select>', {
            'data-variant-default-select': '',
            'data-attribute-id': attributeId,
            class: 'rounded-lg border border-interactive bg-canvas px-2 py-1 text-sm text-ink',
        });

        selection[attributeId].forEach((valueId) => {
            const $checkbox = $group.find(`[data-variant-value-checkbox][value="${valueId}"]`);
            const label = $checkbox.closest('label').text().trim();
            $select.append($('<option>', { value: valueId, text: label }));
        });

        $row.append($select);
        $container.append($row);
    });
}

function readDefaultValues($root) {
    const values = {};

    $root.find('[data-variant-default-select]').each(function () {
        values[String($(this).data('attributeId'))] = Number($(this).val());
    });

    return values;
}

function updatePreview($root) {
    const selection = readSelection($root);

    if (Object.keys(selection).length === 0) {
        $root.find('[data-variant-preview-text]').text('');
        $root.find('[data-variant-generate-button]').prop('disabled', false);
        return;
    }

    Ajax.request({
        url: $root.data('previewUrl'),
        method: 'POST',
        data: { attributes: selection },
        key: 'variant-matrix-preview',
    })
        .done((response) => {
            $root.find('[data-variant-preview-text]').text(
                Ajax.lang('admin_variant_preview_summary', {
                    total: response.total,
                    new: response.new,
                    kept: response.kept,
                    removed: response.removed,
                })
            );

            const overLimit = response.total > Number($root.data('maxCombinations'));
            $root.find('[data-variant-generate-button]').prop('disabled', overLimit);

            if (overLimit) {
                $root.find('[data-variant-preview-text]').append(
                    $('<span>', { class: 'ms-2 text-danger', text: Ajax.lang('admin_variant_limit_warning', { limit: $root.data('maxCombinations') }) })
                );
            }
        })
        .fail(() => {
            $root.find('[data-variant-preview-text]').text('');
        });
}

function initBuilder($root) {
    let timer = null;

    $root.on('change', '[data-variant-value-checkbox]', function () {
        renderDefaultValueSelects($root);
        clearTimeout(timer);
        timer = setTimeout(() => updatePreview($root), PREVIEW_DEBOUNCE_MS);
    });

    $root.on('click', '[data-variant-generate-button]', function () {
        const selection = readSelection($root);

        if (Object.keys(selection).length === 0) return;

        const run = () => {
            Ajax.request({
                url: $root.data('generateUrl'),
                method: 'POST',
                data: { attributes: selection, default_values: readDefaultValues($root) },
                $trigger: $(this),
            })
                .done((response) => {
                    Toast.success(response.message || '');
                    window.location.reload();
                })
                .fail((xhr) => {
                    if (xhr.status === 422) {
                        Toast.error(xhr.responseJSON?.message || '');
                        renderBlockedVariants($root, xhr.responseJSON?.variants || []);
                    }
                });
        };

        // A crude client-side estimate is enough here - the real number
        // (from the last preview response) already gated the button's
        // disabled state above the limit; this is only about warning
        // before a LARGE (but still allowed) generate, not re-validating
        // the limit itself.
        const estimatedTotal = Object.values(selection).reduce((total, values) => total * values.length, 1);

        if (estimatedTotal > LARGE_GENERATE_CONFIRM_THRESHOLD) {
            window.Dersey?.confirmAction?.(Ajax.lang('admin_variant_confirm_large_generate', { count: estimatedTotal }), run);
        } else {
            run();
        }
    });
}

function renderBlockedVariants($root, variants) {
    const $list = $root.find('[data-variant-blocked-list]');
    $list.remove();

    if (variants.length === 0) return;

    const $box = $('<div>', {
        'data-variant-blocked-list': '',
        class: 'mt-3 rounded-lg border border-danger/30 bg-danger/10 p-3 text-sm text-danger',
    });

    variants.forEach((variant) => {
        const $row = $('<p>');
        $row.text(`${variant.label} — ${variant.reasons.join('، ')}`);
        $box.append($row);
    });

    $root.find('[data-variant-preview-text]').after($box);
}

let dirty = false;

function initDirtyTracking($root) {
    $root.on('input change', '[data-variant-sku], [data-variant-price], [data-variant-compare-price], [data-variant-initial-stock], [data-variant-active-toggle]', () => {
        dirty = true;
    });

    window.addEventListener('beforeunload', (event) => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });
}

function initFilter($root) {
    $root.on('input', '[data-variant-filter]', function () {
        const term = $(this).val().trim().toLowerCase();

        $root.find('[data-variant-row]').each(function () {
            const $row = $(this);
            const text = $row.text().toLowerCase();
            $row.toggle(term === '' || text.includes(term));
        });
    });
}

function updateCounts($root) {
    const $rows = $root.find('[data-variant-row]');
    const active = $rows.filter('[data-variant-active="1"]').length;
    const inactive = $rows.length - active;

    $root.find('[data-variant-counts]').text(Ajax.lang('admin_variant_counts', { active, inactive }));
}

function initActiveToggle($root) {
    $root.on('change', '[data-variant-active-toggle]', function () {
        const $checkbox = $(this);
        const $row = $checkbox.closest('[data-variant-row]');
        const isActive = $checkbox.is(':checked');
        const url = $root.data('toggleUrlTemplate').replace('__ID__', $row.data('variantId'));

        Ajax.request({ url, method: 'POST', data: { is_active: isActive } })
            .done((response) => {
                $row.attr('data-variant-active', isActive ? '1' : '0');
                $row.attr('data-variant-version', response.version);
                updateCounts($root);
            })
            .fail(() => {
                // Revert the checkbox - the request failed, the row's real
                // state on the server never changed.
                $checkbox.prop('checked', !isActive);
            });
    });

    updateCounts($root);
}

function initBulkPrice($root) {
    $root.on('click', '[data-variant-bulk-apply]', function () {
        const price = $root.find('[data-variant-bulk-price]').val();
        if (price === '') return;

        $root.find('[data-variant-row]:visible [data-variant-price]').val(price);
        dirty = true;
    });
}

function collectRows($root) {
    return $root
        .find('[data-variant-row]')
        .map(function () {
            const $row = $(this);
            const initialStock = $row.find('[data-variant-initial-stock]').val();

            return {
                id: $row.data('variantId'),
                version: $row.data('variantVersion'),
                sku: $row.find('[data-variant-sku]').val(),
                price: $row.find('[data-variant-price]').val() || null,
                compare_at_price: $row.find('[data-variant-compare-price]').val() || null,
                is_active: $row.find('[data-variant-active-toggle]').is(':checked'),
                initial_stock: initialStock ? Number(initialStock) : null,
            };
        })
        .get();
}

function initSave($root) {
    $root.on('click', '[data-variant-save-button]', function () {
        const rows = collectRows($root);
        if (rows.length === 0) return;

        Ajax.request({
            url: $root.data('updateUrl'),
            method: 'PUT',
            data: { rows },
            $trigger: $(this),
        })
            .done((response) => {
                dirty = false;
                Toast.success(response.message || '');
                window.location.reload();
            })
            .fail((xhr) => {
                if (xhr.status === 409) {
                    Toast.error(xhr.responseJSON?.message || '');
                    renderConflictingVariants($root, xhr.responseJSON?.variants || []);
                }
            });
    });
}

function renderConflictingVariants($root, variants) {
    const $list = $root.find('[data-variant-conflict-list]');
    $list.remove();

    if (variants.length === 0) return;

    const $box = $('<div>', {
        'data-variant-conflict-list': '',
        class: 'mt-3 rounded-lg border border-danger/30 bg-danger/10 p-3 text-sm text-danger',
    });

    $box.append($('<p>', { class: 'font-medium', text: Ajax.lang('admin_variant_conflict_list_title') }));

    variants.forEach((variant) => {
        $box.append($('<p>', { text: variant.label }));
    });

    const $reload = $('<button>', {
        type: 'button',
        class: 'mt-2 rounded-lg border border-danger/30 px-3 py-1 text-sm font-medium text-danger hover:bg-danger/10',
        text: Ajax.lang('admin_variant_reload_button'),
    }).on('click', () => window.location.reload());

    $box.append($reload);
    $root.find('[data-variant-save-button]').closest('div').after($box);
}

function init(el) {
    const $root = $(el);
    if (!$root.length) return;

    initBuilder($root);
    initDirtyTracking($root);
    initFilter($root);
    initActiveToggle($root);
    initBulkPrice($root);
    initSave($root);
}

export default { init };
