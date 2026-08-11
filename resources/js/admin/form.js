import $ from 'jquery';

/**
 * Extends core/form.js (Batch 1.4) rather than replacing it - that file's
 * own init() already handles the actual submit/422-field-error rendering
 * for every [data-ajax-form] on the page, storefront included. This only
 * adds one admin-specific behavior on top: when a 422 lands on a form
 * marked [data-translatable-form] (x-admin.form :translatable="true") and
 * the first errored field belongs to a language that isn't the currently
 * active x-admin.translatable-tabs tab, switch to that tab automatically -
 * otherwise the error is rendered but invisible (hidden tab panel).
 *
 * Field naming CONVENTION this depends on: translatable inputs are named
 * "translations[{locale}][{field}]" (e.g. "translations[en][name]") -
 * whichever admin view builds a translatable form must follow this so the
 * locale can be read back out of the error key.
 */

function localeFromFieldName(field) {
    const match = field.match(/^translations\[([a-z]{2})]/);

    return match ? match[1] : null;
}

function init() {
    $(document).on('dersey:form-error', '[data-translatable-form]', function (event, errors) {
        const $form = $(this);
        const $tabsRoot = $form.find('[data-translatable-tabs]').first();

        if (!$tabsRoot.length) return;

        const firstField = Object.keys(errors)[0];
        const locale = firstField ? localeFromFieldName(firstField) : null;

        if (locale && window.Dersey?.activateTranslatableTab) {
            window.Dersey.activateTranslatableTab($tabsRoot, locale);
        }
    });
}

export default { init };
