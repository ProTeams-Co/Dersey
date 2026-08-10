import NProgress from 'nprogress';
import 'nprogress/nprogress.css';

/**
 * Reference-counted so that N concurrent requests don't finish the progress
 * bar the moment the *first* one completes — it only reaches done() once
 * every in-flight request has settled.
 */
let activeRequests = 0;
let configured = false;

function init() {
    if (configured) return;
    configured = true;
    NProgress.configure({ showSpinner: false });
}

function start() {
    activeRequests += 1;
    if (activeRequests === 1) NProgress.start();
}

function done() {
    activeRequests = Math.max(0, activeRequests - 1);
    if (activeRequests === 0) NProgress.done();
}

/**
 * Presentational only — the actual spinner visual is a CSS hook (`.is-loading`)
 * for a later batch's button styling, not something this module renders itself.
 */
function disableButton($btn) {
    if (!$btn || !$btn.length) return;
    $btn.prop('disabled', true).attr('aria-busy', 'true').addClass('is-loading');
}

function enableButton($btn) {
    if (!$btn || !$btn.length) return;
    $btn.prop('disabled', false).removeAttr('aria-busy').removeClass('is-loading');
}

function showSkeleton($el) {
    if (!$el || !$el.length) return;
    $el.attr('aria-busy', 'true').addClass('animate-pulse');
}

function hideSkeleton($el) {
    if (!$el || !$el.length) return;
    $el.removeAttr('aria-busy').removeClass('animate-pulse');
}

export default { init, start, done, disableButton, enableButton, showSkeleton, hideSkeleton };
