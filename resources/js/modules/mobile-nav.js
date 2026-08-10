import $ from 'jquery';
import Modal from '../core/modal';

const DRAWER_SELECTOR = '#mobile-nav-drawer';
const BACKDROP_SELECTOR = '#mobile-nav-backdrop';

function toggleAccordion($accordionTrigger) {
    const slug = $accordionTrigger.data('accordionTrigger');
    const $panel = $(`[data-accordion-panel="${slug}"]`);
    const isOpen = $accordionTrigger.attr('aria-expanded') === 'true';

    $accordionTrigger.attr('aria-expanded', String(!isOpen));
    $panel.attr('hidden', isOpen);
    $accordionTrigger.find('[data-accordion-chevron]').toggleClass('rotate-180', !isOpen);
}

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

    $(document).on('click', '[data-accordion-trigger]', function () {
        toggleAccordion($(this));
    });
}

export default { init };
