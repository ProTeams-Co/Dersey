import $ from 'jquery';
import Ajax from '../core/ajax';
import Toast from '../core/toast';
import Editor from './editor';

/**
 * One file for the whole product create/edit screen (Task 8) - tab
 * switching + hash, live slug generation, SKU availability check, unsaved-
 * changes warning, lazy CKEditor on the translations tab specifically
 * (not editor.js's own page-load bulk scan - see _tab-translations.blade.php),
 * and the SEO tab's char counter + SERP preview. No new libraries, no
 * external state management - plain jQuery + the existing Ajax wrapper
 * (core/ajax.js, Batch 1.4), same as every other admin screen.
 */

const SKU_CHECK_DEBOUNCE_MS = 400;

let dirty = false;

function markDirty() {
    dirty = true;
}

function initUnsavedChangesWarning() {
    $(document).on('input change', '[data-product-tab-form] input, [data-product-tab-form] textarea, [data-product-tab-form] select', markDirty);

    $(document).on('dersey:form-success', '[data-product-tab-form]', () => {
        dirty = false;
    });

    window.addEventListener('beforeunload', (event) => {
        if (!dirty) return;
        event.preventDefault();
        // Chrome ignores a custom returnValue string these days but still
        // requires one to be set to show its own built-in prompt at all.
        event.returnValue = '';
    });
}

function initTabs() {
    const $root = $('[data-product-tabs]');
    if (!$root.length) return;

    const $triggers = $root.find('[data-product-tab-trigger]');
    const $panels = $root.find('[data-product-tab-panel]');
    const openedTabs = new Set();

    function activate(tab) {
        $triggers.each(function () {
            const isActive = $(this).data('productTabTrigger') === tab;
            $(this).toggleClass('border-primary text-ink', isActive);
            $(this).toggleClass('border-transparent text-muted', !isActive);
        });

        $panels.each(function () {
            $(this).prop('hidden', $(this).data('productTabPanel') !== tab);
        });

        if (!openedTabs.has(tab)) {
            openedTabs.add(tab);
            initLazyEditorsIn($panels.filter(`[data-product-tab-panel="${tab}"]`));
        }
    }

    $triggers.on('click', function () {
        const tab = $(this).data('productTabTrigger');
        window.location.hash = tab;
        activate(tab);
    });

    const initial = (window.location.hash || '#basic').slice(1);
    const validInitial = $triggers.filter(`[data-product-tab-trigger="${initial}"]`).length ? initial : 'basic';
    activate(validInitial);
}

function initLazyEditorsIn($panel) {
    $panel.find('[data-editor-lazy]').each(function () {
        Editor.initEditor(this);
    });
}

function initSlugGeneration() {
    $(document).on('input', '[data-slug-source]', function () {
        const locale = $(this).data('slugSource');
        const $slugField = $(this).closest('form, [data-tab-panel]').find(`[name="translations[${locale}][slug]"]`);

        if (!$slugField.length || $slugField.data('touched')) return;

        $slugField.val(slugify($(this).val(), locale));
    });

    $(document).on('input', 'input[name$="[slug]"]', function () {
        $(this).data('touched', true);
    });
}

function slugify(text, locale) {
    // A client-side preview only - the server (App\Support\Slug, already
    // Arabic-aware) is the real, authoritative generator on save. This
    // just avoids the field sitting empty while typing.
    const normalized = text.trim().toLowerCase();

    if (locale === 'ar') {
        return normalized.replace(/\s+/g, '-').replace(/[^\p{L}\p{N}-]/gu, '');
    }

    return normalized
        .replace(/\s+/g, '-')
        .replace(/[^a-z0-9-]/g, '');
}

function initSkuCheck() {
    let timer = null;

    $(document).on('input', '[data-sku-check]', function () {
        const $field = $(this);
        const $status = $field.closest('div').next('[data-sku-status]');
        const currentSku = $field.data('skuCurrent');

        clearTimeout(timer);

        const value = $field.val().trim();
        if (!value || value === currentSku) {
            $status.text('');
            return;
        }

        $status.text(Ajax.lang('products_sku_checking') || '');

        timer = setTimeout(() => {
            Ajax.request({
                url: '/admin/products/sku-check',
                method: 'GET',
                data: { sku: value, ignore_id: $field.data('skuIgnoreId') || null },
                key: 'sku-check',
            })
                .done((response) => {
                    $status
                        .text(response.available ? Ajax.lang('products_sku_available') : Ajax.lang('products_sku_taken'))
                        .toggleClass('text-danger', !response.available)
                        .toggleClass('text-success', response.available);
                })
                .fail(() => {});
        }, SKU_CHECK_DEBOUNCE_MS);
    });
}

function initSeoLivePreview() {
    function sync($panel) {
        const title = $panel.find('[data-seo-title]').val() || '';
        const description = $panel.find('[data-seo-description]').val() || '';

        $panel.find('[data-seo-preview-title]').text(title);
        $panel.find('[data-seo-preview-description]').text(description);
    }

    $(document).on('input', '[data-seo-title], [data-seo-description]', function () {
        sync($(this).closest('[data-tab-panel]'));
    });

    $('[data-tab-panel]').each(function () {
        sync($(this));
    });
}

function initPublishButton() {
    $(document).on('click', '[data-publish-button]:not(:disabled)', function () {
        const $button = $(this);

        Ajax.request({
            url: $button.data('publishUrl'),
            method: 'POST',
            data: { status: 'published' },
            $trigger: $button,
        }).done(() => {
            window.location.reload();
        });
    });
}

function initTabSaveToast() {
    $(document).on('dersey:form-success', '[data-product-tab-form]', (event, response) => {
        Toast.success(response?.message || '');
    });
}

function init() {
    if (!$('[data-product-tabs], [data-product-tab-form]').length) return;

    initTabs();
    initUnsavedChangesWarning();
    initSlugGeneration();
    initSkuCheck();
    initSeoLivePreview();
    initPublishButton();
    initTabSaveToast();
}

export default { init };
