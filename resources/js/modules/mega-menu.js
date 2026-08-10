import $ from 'jquery';

/**
 * One shared panel container, one open slug at a time - see
 * components/layout/mega-menu.blade.php for why. Hover opens/schedules a
 * close with a delay so the pointer can travel from the trigger button down
 * into the panel without it closing mid-transit; click (touch has no
 * meaningful hover) toggles directly; focus (Tab) opens exactly like hover
 * so keyboard users reach the same panel.
 */

const CLOSE_DELAY_MS = 200;

let openSlug = null;
let closeTimer = null;
// Escape returns focus to the trigger it closed, which would otherwise
// immediately re-fire the focusin handler below and reopen the panel it
// just closed. Set right before that one focus() call, consumed by the
// very next focusin so a real, later Tab into the trigger still opens it.
let suppressNextFocusOpen = false;

function $panelFor(slug) {
    return $(`#mega-menu [data-mega-panel="${slug}"]`);
}

function $triggerFor(slug) {
    return $(`[data-mega-trigger="${slug}"]`);
}

function cancelScheduledClose() {
    if (!closeTimer) return;
    clearTimeout(closeTimer);
    closeTimer = null;
}

function open(slug) {
    cancelScheduledClose();
    if (openSlug === slug) return;

    if (openSlug) {
        $panelFor(openSlug).attr('hidden', true);
        $triggerFor(openSlug).attr('aria-expanded', 'false');
    }

    $('#mega-menu').removeAttr('hidden');
    $panelFor(slug).removeAttr('hidden');
    $triggerFor(slug).attr('aria-expanded', 'true');
    openSlug = slug;
}

function closeNow() {
    cancelScheduledClose();
    if (!openSlug) return;

    $panelFor(openSlug).attr('hidden', true);
    $triggerFor(openSlug).attr('aria-expanded', 'false');
    $('#mega-menu').attr('hidden', true);
    openSlug = null;
}

function scheduleClose() {
    cancelScheduledClose();
    closeTimer = setTimeout(closeNow, CLOSE_DELAY_MS);
}

function isWithinMegaMenu(el) {
    return !!el && $(el).closest('[data-mega-trigger], #mega-menu').length > 0;
}

function init() {
    if (!$('#mega-menu').length) return;

    $(document).on('mouseenter', '[data-mega-trigger]', function () {
        open($(this).data('megaTrigger'));
    });
    $(document).on('mouseleave', '[data-mega-trigger]', scheduleClose);

    $('#mega-menu')
        .on('mouseenter', cancelScheduledClose)
        .on('mouseleave', scheduleClose);

    $(document).on('click', '[data-mega-trigger]', function (event) {
        event.preventDefault();
        const slug = $(this).data('megaTrigger');
        if (openSlug === slug) {
            closeNow();
        } else {
            open(slug);
        }
    });

    $(document).on('focusin', '[data-mega-trigger]', function () {
        if (suppressNextFocusOpen) {
            suppressNextFocusOpen = false;
            return;
        }
        open($(this).data('megaTrigger'));
    });

    // relatedTarget is the element about to receive focus - only a real
    // exit (moving somewhere outside every trigger and the open panel)
    // should close it, not moving between two items that are both inside.
    $(document).on('focusout', function (event) {
        if (!openSlug || isWithinMegaMenu(event.relatedTarget)) return;
        closeNow();
    });

    $(document).on('keydown', function (event) {
        if (!openSlug) return;

        if (event.key === 'Escape') {
            const $trigger = $triggerFor(openSlug);
            closeNow();
            suppressNextFocusOpen = true;
            $trigger.trigger('focus');
            return;
        }

        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
        if (!isWithinMegaMenu(event.target)) return;

        const $focusable = $panelFor(openSlug).find('a, button').filter(':visible');
        if (!$focusable.length) return;

        event.preventDefault();
        const currentIndex = $focusable.index(document.activeElement);
        const lastIndex = $focusable.length - 1;
        const nextIndex = event.key === 'ArrowDown'
            ? (currentIndex < 0 ? 0 : (currentIndex + 1) % $focusable.length)
            : (currentIndex <= 0 ? lastIndex : currentIndex - 1);

        $focusable.get(nextIndex).focus();
    });
}

export default { init };
