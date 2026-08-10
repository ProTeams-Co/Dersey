import $ from 'jquery';
import Events from './events';

/**
 * Generic open/close controller — event delegation only, since a modal's
 * trigger button is frequently injected into the page by an Ajax response
 * after this module has already initialized.
 *
 * Markup contract:
 *   <button data-action="modal-open" data-modal-target="#my-modal">...</button>
 *   <div id="my-modal" data-modal hidden>
 *     <button data-action="modal-close">...</button>
 *     ...
 *   </div>
 */

function open($modal) {
    if (!$modal || !$modal.length) return;
    $modal.removeAttr('hidden').attr('aria-hidden', 'false');
    $('body').addClass('overflow-hidden');
    Events.emit('modal:opened', $modal);
}

function close($modal) {
    if (!$modal || !$modal.length) return;
    $modal.attr('hidden', true).attr('aria-hidden', 'true');
    $('body').removeClass('overflow-hidden');
    Events.emit('modal:closed', $modal);
}

function init() {
    $(document).on('click', '[data-action="modal-open"]', function () {
        const target = $(this).data('modalTarget');
        if (target) open($(target));
    });

    $(document).on('click', '[data-action="modal-close"]', function () {
        close($(this).closest('[data-modal]'));
    });

    // Backdrop click — only when the click lands on the modal root itself,
    // not on any of its content.
    $(document).on('click', '[data-modal]', function (event) {
        if (event.target === this) close($(this));
    });

    $(document).on('keydown', function (event) {
        if (event.key !== 'Escape') return;
        const $open = $('[data-modal]:not([hidden])');
        if ($open.length) close($open.last());
    });
}

export default { init, open, close };
