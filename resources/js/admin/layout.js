import $ from 'jquery';
import Ajax from '../core/ajax';
import Modal from '../core/modal';

/**
 * Sidebar collapse/expand (desktop), the mobile off-canvas drawer, the
 * collapsible menu groups, and the topbar's notification/account dropdowns
 * - all small, anchored/inline interactions that don't fit core/modal.js's
 * full-overlay contract (focus trap, scroll lock), so they live here
 * instead of being forced into that stack.
 */

const SIDEBAR_COOKIE = 'admin_sidebar_collapsed';
const SIDEBAR_COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

function setSidebarCookie(collapsed) {
    document.cookie = `${SIDEBAR_COOKIE}=${collapsed ? '1' : '0'}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}; SameSite=Lax`;
}

function initCollapseToggle() {
    $(document).on('click', '[data-sidebar-collapse-toggle]', function () {
        const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        setSidebarCookie(collapsed);

        $(this).attr(
            'aria-label',
            Ajax.lang(collapsed ? 'admin_sidebar_expand' : 'admin_sidebar_collapse')
        );
    });
}

function initMobileDrawer() {
    $(document).on('click', '[data-sidebar-open]', function () {
        document.documentElement.classList.add('sidebar-open');
    });

    $(document).on('click', '[data-sidebar-backdrop]', function () {
        document.documentElement.classList.remove('sidebar-open');
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            document.documentElement.classList.remove('sidebar-open');
        }
    });
}

function initGroupToggle() {
    $(document).on('click', '[data-sidebar-group-toggle]', function () {
        const $panel = $(this).siblings('[data-sidebar-group-panel]');
        const open = $panel.attr('data-sidebar-group-open') !== undefined;

        $panel.toggleClass('hidden', open).attr('data-sidebar-group-open', open ? null : '');
    });
}

function closeAllDropdowns(except = null) {
    $('[data-dropdown-panel]').not(except).attr('hidden', true);
    $('[data-dropdown-toggle]').not(except ? except.siblings('[data-dropdown-toggle]') : null).attr('aria-expanded', 'false');
}

function initDropdowns() {
    $(document).on('click', '[data-dropdown-toggle]', function (event) {
        event.stopPropagation();

        const $panel = $(this).siblings('[data-dropdown-panel]');
        const isOpen = !$panel.attr('hidden');

        closeAllDropdowns($panel);
        $panel.attr('hidden', isOpen);
        $(this).attr('aria-expanded', isOpen ? 'false' : 'true');
    });

    $(document).on('click', function () {
        closeAllDropdowns();
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllDropdowns();
        }
    });
}

/**
 * A single shared confirm dialog (x-admin.confirm, mounted once in
 * layouts/admin.blade.php) backs every [data-confirm] trigger in the admin
 * panel - a row-delete link, a bulk-action button, anything destructive.
 * The trigger's default action (link navigation, form submit) is
 * cancelled and only actually happens if the dialog's accept button is
 * clicked; window.Dersey.confirmAction() is the same mechanism for
 * programmatic callers (admin/table.js's bulk actions).
 */
let pendingConfirmAction = null;

function submitAsPost(url, method) {
    const $form = $('<form>', { method: 'POST', action: url, class: 'hidden' });
    $form.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));

    if (method && method.toUpperCase() !== 'POST') {
        $form.append($('<input>', { type: 'hidden', name: '_method', value: method }));
    }

    $('body').append($form);
    $form.trigger('submit');
}

function openConfirm(message, onAccept) {
    pendingConfirmAction = onAccept;

    const $dialog = $('#admin-confirm-dialog');
    $dialog.find('[data-confirm-message]').text(message || '');
    Modal.open($dialog);
}

function initConfirmDialog() {
    $(document).on('click', '[data-confirm]', function (event) {
        const $trigger = $(this);
        event.preventDefault();

        openConfirm($trigger.data('confirmMessage'), () => {
            if ($trigger.is('a')) {
                const method = $trigger.data('rowActionMethod');
                if (method) {
                    submitAsPost($trigger.attr('href'), method);
                } else {
                    window.location.href = $trigger.attr('href');
                }
            } else if ($trigger.is(':submit')) {
                $trigger.closest('form').trigger('submit');
            }
        });
    });

    $(document).on('click', '[data-confirm-accept]', function () {
        Modal.close($('#admin-confirm-dialog'));

        if (pendingConfirmAction) {
            pendingConfirmAction();
            pendingConfirmAction = null;
        }
    });

    window.Dersey = window.Dersey || {};
    window.Dersey.confirmAction = openConfirm;
}

function init() {
    initCollapseToggle();
    initMobileDrawer();
    initGroupToggle();
    initDropdowns();
    initConfirmDialog();
}

export default { init };
