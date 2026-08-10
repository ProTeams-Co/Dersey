import $ from 'jquery';
import Toast from './toast';
import Loader from './loader';
import Events from './events';

/**
 * The single choke point every Ajax call in the app goes through — jQuery's
 * $.ajax is the only thing that ever talks to the network here (no fetch(),
 * no axios), so every request gets the same CSRF/locale headers and the same
 * per-status-code error handling without every caller re-implementing it.
 *
 * SECURITY: never render a response body with .html() — always .text() —
 * unless the content is explicitly known to be trusted, safe HTML (e.g. a
 * partial rendered server-side specifically for that purpose). Untrusted
 * text injected via .html() is a direct DOM XSS vector. This module itself
 * never touches the DOM with response content at all; that's every caller's
 * own responsibility to get right.
 */

// url+method (or a caller-supplied key) -> in-flight jqXHR. Lets a second
// request for the same thing (e.g. live search on every keystroke) cancel
// the first instead of both racing to update the page.
const inFlight = new Map();

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function setCsrfToken(token) {
    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
}

/**
 * Pulls a message out of window.Dersey.lang (injected server-side via
 * @json() — see resources/lang/{ar,en}/js.php) and fills in :placeholder
 * tokens. Never hardcode user-facing text in JS — this is the only place
 * that's allowed to fall back to a raw key, and only if the translation is
 * somehow missing, as a last-resort so the UI never shows nothing at all.
 */
function lang(key, replacements = {}) {
    let message = window.Dersey?.lang?.[key] ?? key;

    for (const [search, value] of Object.entries(replacements)) {
        message = message.replace(`:${search}`, value);
    }

    return message;
}

function request(options) {
    const {
        url,
        method = 'GET',
        data,
        key = null,
        $trigger = null,
        headers = {},
        retriedAfter419 = false,
        ...rest
    } = options;

    const dedupeKey = key ?? `${method}:${url}`;

    if (inFlight.has(dedupeKey)) {
        inFlight.get(dedupeKey).abort();
        inFlight.delete(dedupeKey);
    }

    if ($trigger) Loader.disableButton($trigger);
    Loader.start();

    const jqxhr = $.ajax({
        url,
        method,
        data,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            'X-Locale': window.Dersey?.locale ?? 'ar',
            ...headers,
        },
        ...rest,
    });

    inFlight.set(dedupeKey, jqxhr);

    jqxhr.always(() => {
        if (inFlight.get(dedupeKey) === jqxhr) inFlight.delete(dedupeKey);
        Loader.done();
        if ($trigger) Loader.enableButton($trigger);
    });

    jqxhr.fail((xhr, textStatus) => {
        // Our own inFlight.get(...).abort() call above lands here too —
        // that's an intentional cancellation, not a real failure, so it
        // must never toast or trigger retry/reload logic.
        if (textStatus === 'abort') return;

        handleError(xhr, textStatus, options, retriedAfter419);
    });

    return jqxhr;
}

function handleError(xhr, textStatus, originalOptions, retriedAfter419) {
    const status = xhr.status;

    if (status === 419 && !retriedAfter419) {
        refreshCsrfAndRetry(originalOptions);
        return;
    }

    if (status === 419) {
        Toast.error(lang('error_session_expired'));
        window.location.reload();
        return;
    }

    if (status === 422) {
        // Deliberately no toast — the caller (e.g. core/form.js) reads
        // xhr.responseJSON.errors itself and renders them under each field.
        // A generic toast on top of field-level errors would be noise.
        return;
    }

    if (status === 429) {
        const retryAfter = xhr.getResponseHeader('Retry-After') ?? '?';
        Toast.warning(lang('error_rate_limited', { seconds: retryAfter }));
        return;
    }

    if (status === 401 || status === 403) {
        Toast.error(lang('error_unauthorized'));
        if (status === 401 && window.Dersey?.routes?.login) {
            window.location.href = window.Dersey.routes.login;
        }
        return;
    }

    if (status === 404) {
        Toast.error(lang('error_not_found'));
        return;
    }

    if (status >= 500) {
        // Never surface xhr.responseJSON.message/trace here, even in a
        // local/debug environment where Laravel's own response happens to
        // include them — the toast is always the generic, translated string.
        Toast.error(lang('error_server'));
        return;
    }

    if (status === 0 || textStatus === 'timeout') {
        Toast.retry(lang('error_network'), () => request(originalOptions));
        return;
    }

    Events.emit('ajax:unhandled-error', xhr, textStatus);
}

function refreshCsrfAndRetry(originalOptions) {
    const csrfRoute = window.Dersey?.routes?.csrfToken;

    if (!csrfRoute) {
        Toast.error(lang('error_session_expired'));
        window.location.reload();
        return;
    }

    $.ajax({ url: csrfRoute, method: 'GET', headers: { Accept: 'application/json' } })
        .done((response) => {
            if (response?.token) setCsrfToken(response.token);
            request({ ...originalOptions, retriedAfter419: true });
        })
        .fail(() => {
            Toast.error(lang('error_session_expired'));
            window.location.reload();
        });
}

export default { request, lang, csrfToken };
