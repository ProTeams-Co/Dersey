import $ from 'jquery';
import Events from './events';

/**
 * Shared overlay-layer controller — modals, drawers, and full-screen
 * overlays are all the same underlying pattern (show, trap focus, lock
 * scroll, restore focus on close), so they all go through the same stack
 * here instead of each reimplementing it - including which backdrop element
 * (if any) belongs to a layer, passed once via open()'s options and then
 * shown/hidden together with it on every close path (Escape, backdrop
 * click, or an explicit close() call alike) so nothing can forget to sync
 * it. A module built on top of this (modules/mobile-nav.js, cart-drawer.js,
 * search.js) keeps only what's actually specific to it — accordion
 * behavior, search input handling, which trigger/backdrop pair is its own.
 *
 * Markup contract for the built-in [data-action="modal-open"] delegation
 * below (used by components/developer-modal.blade.php):
 *   <button data-action="modal-open" data-modal-target="#my-modal">...</button>
 *   <div id="my-modal" data-modal hidden>
 *     <button data-action="modal-close">...</button>
 *     ...
 *   </div>
 * A module that manages its own trigger matching (the three above) calls
 * open()/close() directly instead of relying on this delegation.
 */

const FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])';

// Layers currently open, oldest first - Escape and scroll-lock refcounting
// both key off this array's length rather than any one layer's own state,
// so nesting (e.g. cart-drawer opened on top of mobile-nav) behaves
// correctly: Escape closes only the top, and the page only scrolls again
// once every layer under it has also closed.
const stack = [];

// The one opener element close() should NOT reopen if a 'focusin' listener
// elsewhere reacts to the focus restored below - relevant for a future
// layer opened via hover/focus (like modules/mega-menu.js's own trigger,
// which does not go through this module) rather than the click-only
// triggers all three current consumers use.
let suppressNextFocusOpen = null;

function focusableIn($el) {
    return $el.find(FOCUSABLE_SELECTOR).filter(':visible');
}

function lockScroll() {
    if (stack.length !== 1) return; // a lower layer already locked it

    const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    document.body.style.overflow = 'hidden';
    if (scrollbarWidth > 0) document.body.style.paddingInlineEnd = `${scrollbarWidth}px`;
}

function unlockScroll() {
    if (stack.length) return; // a lower layer is still open

    document.body.style.overflow = '';
    document.body.style.paddingInlineEnd = '';
}

function trapFocus(event) {
    if (event.key !== 'Tab' || !stack.length) return;

    const $focusable = focusableIn(stack[stack.length - 1].$el);
    if (!$focusable.length) return;

    const first = $focusable.get(0);
    const last = $focusable.get($focusable.length - 1);

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

function open($el, options = {}) {
    if (!$el || !$el.length || stack.some((layer) => layer.$el.is($el))) return;

    const { $trigger = null, $backdrop = null } = options;

    $el.removeAttr('hidden').attr('aria-hidden', 'false');
    if ($backdrop) $backdrop.removeAttr('hidden');

    stack.push({ $el, $trigger, $backdrop });
    lockScroll();

    const $focusable = focusableIn($el);
    if ($focusable.length) $focusable.first().trigger('focus');

    Events.emit('modal:opened', $el);
}

function close($el) {
    if (!$el || !$el.length) return;

    const index = stack.findIndex((layer) => layer.$el.is($el));
    if (index === -1) return;

    const [layer] = stack.splice(index, 1);

    $el.attr('hidden', true).attr('aria-hidden', 'true');
    if (layer.$backdrop) layer.$backdrop.attr('hidden', true);
    unlockScroll();

    if (layer.$trigger && layer.$trigger.length) {
        suppressNextFocusOpen = layer.$trigger.get(0);
        layer.$trigger.trigger('focus');
    }

    Events.emit('modal:closed', $el);
}

function closeTop() {
    if (stack.length) close(stack[stack.length - 1].$el);
}

function init() {
    $(document).on('click', '[data-action="modal-open"]', function () {
        const target = $(this).data('modalTarget');
        if (target) open($(target), { $trigger: $(this) });
    });

    $(document).on('click', '[data-action="modal-close"]', function () {
        close($(this).closest('[data-modal]'));
    });

    // Backdrop click — only when the click lands on the modal root itself,
    // not on any of its content.
    $(document).on('click', '[data-modal]', function (event) {
        if (event.target === this) close($(this));
    });

    $(document).on('focusin', function (event) {
        if (suppressNextFocusOpen === event.target) suppressNextFocusOpen = null;
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closeTop();
            return;
        }
        trapFocus(event);
    });
}

export default { init, open, close, closeTop };
