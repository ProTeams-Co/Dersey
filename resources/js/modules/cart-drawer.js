import $ from 'jquery';
import Modal from '../core/modal';

const DRAWER_SELECTOR = '#cart-drawer';
const BACKDROP_SELECTOR = '#cart-drawer-backdrop';

function init() {
    const $drawer = $(DRAWER_SELECTOR);
    if (!$drawer.length) return;

    $(document).on('click', `[data-action="drawer-open"][data-drawer-target="${DRAWER_SELECTOR}"]`, function (event) {
        event.preventDefault();
        Modal.open($drawer, { $trigger: $(this), $backdrop: $(BACKDROP_SELECTOR) });
    });

    $(document).on('click', `[data-action="drawer-close"][data-drawer-target="${DRAWER_SELECTOR}"]`, function (event) {
        event.preventDefault();
        Modal.close($drawer);
    });

    $(BACKDROP_SELECTOR).on('click', function () {
        Modal.close($drawer);
    });
}

export default { init };
