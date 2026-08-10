import { Notyf, NotyfEvent } from 'notyf';
import 'notyf/notyf.min.css';
// Must come after notyf's own CSS — see the file for why (logical-property
// override needs to win the cascade, not just match specificity).
import '../../css/notyf.css';

/**
 * SECURITY: Notyf renders the `message` option via `.innerHTML` internally
 * (not `.textContent`) — see node_modules/notyf/notyf.js. That means every
 * string passed into the functions below is HTML, not plain text, from
 * Notyf's point of view. Never pass raw/untrusted server text (a raw
 * validation message with unescaped user input in it, an exception message,
 * etc.) into any of these — only static strings from window.Dersey.lang and
 * safely-numeric interpolations (e.g. a Retry-After seconds count) are safe
 * here. Field-level validation messages have their own, separate rendering
 * path in core/form.js that uses .text(), not a toast.
 */

const MAX_VISIBLE = 3;

let notyf = null;
let active = [];

function untrack(notification) {
    active = active.filter((item) => item !== notification);
}

function push(notification) {
    if (active.length >= MAX_VISIBLE) {
        notyf.dismiss(active[0]);
        untrack(active[0]);
    }

    active.push(notification);
    notification.on(NotyfEvent.Dismiss, () => untrack(notification));

    return notification;
}

function init() {
    if (notyf) return; // idempotent — app.js and admin.js may both call init()

    const dir = window.Dersey?.dir ?? 'ltr';

    notyf = new Notyf({
        duration: 4000,
        ripple: false,
        dismissible: true, // renders a real <button>, so Escape/Tab/Enter dismissal is native — no extra keyboard wiring needed
        position: {
            // Notyf's own position API is physical (left/right), not
            // logical — a third-party library constraint we can't change.
            // What we control is which physical side we pick, and we pick
            // it from the current text direction so the toast still lands
            // on the visual "start" edge in both locales.
            x: dir === 'rtl' ? 'right' : 'left',
            y: 'top',
        },
        types: [
            { type: 'success', background: 'var(--color-success)', duration: 4000 },
            { type: 'error', background: 'var(--color-danger)', duration: 6000 },
            { type: 'warning', background: 'var(--color-warning)', duration: 5000 },
            { type: 'info', background: 'var(--color-accent)', duration: 4000 },
        ],
    });
}

function success(message) {
    return push(notyf.success(message));
}

function error(message) {
    return push(notyf.error(message));
}

function warning(message) {
    return push(notyf.open({ type: 'warning', message }));
}

function info(message) {
    return push(notyf.open({ type: 'info', message }));
}

/**
 * Notyf has no built-in action-button slot (its message is a plain string,
 * not a template) — the whole toast surface is clickable via NotyfEvent.Click
 * though, so a "tap to retry" toast uses that instead of a separate button.
 * The message text itself must say "tap to retry" so this is discoverable.
 */
function retry(message, onRetry) {
    const notification = notyf.open({ type: 'error', message, duration: 8000 });
    notification.on(NotyfEvent.Click, () => {
        notyf.dismiss(notification);
        onRetry();
    });

    return push(notification);
}

export default { init, success, error, warning, info, retry };
