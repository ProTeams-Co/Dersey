import $ from 'jquery';
import Ajax from './ajax';

/**
 * Generic ajax-submit + 422-under-field-error handling — used by both
 * app.js and admin.js (storefront forms need the exact same behavior as
 * admin ones), which is why this lives in core/ rather than admin/ despite
 * admin.js being the only place it was exercised when it was first built.
 *
 * Markup contract: <form data-ajax-form action="..." method="POST">. On a
 * 422 response, errors are rendered under each matching [name] field; every
 * other status code is already handled globally by core/ajax.js.
 */

function clearErrors($form) {
    $form.find('[data-field-error]').remove();
    $form.find('[aria-invalid="true"]').removeAttr('aria-invalid');
}

function renderErrors($form, errors) {
    Object.entries(errors).forEach(([field, messages]) => {
        // :not([type="hidden"]) — a boolean toggle field has TWO elements
        // sharing the same name (the "0" fallback <input type="hidden">
        // plus the real checkbox, see x-form.toggle's docblock); matching
        // both meant .insertAfter() cloned the error <p> after each one,
        // rendering the same message twice at two different, overlapping
        // positions in the form.
        const $field = $form.find(`[name="${field}"]`).not('[type="hidden"]');
        if (!$field.length) return;

        $field.attr('aria-invalid', 'true');

        // .text(), never .html() — messages[0] can originate from a custom
        // validation rule, which is server code but not necessarily free of
        // reflected request input (e.g. a rule that echoes the submitted
        // value back in its message).
        $('<p>', { 'data-field-error': '', class: 'text-sm text-danger mt-1' })
            .text(messages[0])
            .insertAfter($field);
    });
}

function submit($form) {
    clearErrors($form);

    Ajax.request({
        url: $form.attr('action'),
        method: $form.attr('method') || 'POST',
        data: $form.serialize(),
        $trigger: $form.find('[type="submit"]'),
    })
        .done((response) => {
            $form.trigger('dersey:form-success', [response]);

            // Admin CRUD responses (AdminController::respond()) carry a
            // `redirect` target - without navigating there, a successful
            // save just left the admin on the exact same form with no
            // visible confirmation and a re-enabled Save button, which is
            // exactly what invited clicking Save again. Non-admin
            // data-ajax-form users (storefront) simply never send this key,
            // so they're unaffected.
            if (response?.redirect) {
                window.location.href = response.redirect;
            }
        })
        .fail((xhr) => {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                renderErrors($form, xhr.responseJSON.errors);
                // Additive, not a behavior change for existing listeners —
                // admin/form.js uses this to open the right
                // translatable-tabs tab when the first error is in a
                // language that isn't currently visible.
                $form.trigger('dersey:form-error', [xhr.responseJSON.errors]);
            }
        });
}

function init() {
    $(document).on('submit', '[data-ajax-form]', function (event) {
        event.preventDefault();
        submit($(this));
    });
}

export default { init };
