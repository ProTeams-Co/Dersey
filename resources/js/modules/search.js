import $ from 'jquery';
import Modal from '../core/modal';

const OVERLAY_SELECTOR = '#search-overlay';

/**
 * No live search endpoint exists in this batch - this just toggles the
 * placeholder empty/results state locally. The real integration point,
 * once a backend lands, is Dersey.ajax.request({ key: 'search', ... }): the
 * explicit `key` (rather than the default url+method dedupe key) is what
 * lets a fast typist's requests cancel each other via core/ajax.js's
 * inFlight.get(key).abort(), instead of racing an old keystroke's response
 * against a newer one.
 */
function handleInput() {
    const hasValue = $('[data-search-input]').val().length > 0;
    $('[data-search-empty-state]').attr('hidden', hasValue);
    $('[data-search-results]').attr('hidden', !hasValue);
}

function init() {
    if (!$(OVERLAY_SELECTOR).length) return;

    $(document).on('click', '[data-action="search-open"]', function (event) {
        event.preventDefault();
        Modal.open($(OVERLAY_SELECTOR), { $trigger: $(this) });
    });

    $(document).on('click', `[data-action="drawer-close"][data-drawer-target="${OVERLAY_SELECTOR}"]`, function (event) {
        event.preventDefault();
        Modal.close($(OVERLAY_SELECTOR));
    });

    $(document).on('input', '[data-search-input]', handleInput);

    $(document).on('click', '[data-search-term]', function () {
        $('[data-search-input]').val($(this).text().trim()).trigger('focus').trigger('input');
    });
}

export default { init };
