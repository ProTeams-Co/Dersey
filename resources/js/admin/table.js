import $ from 'jquery';
import Ajax from '../core/ajax';

/**
 * Progressive enhancement over [data-admin-table] (x-admin.table.blade.php)
 * - every interaction it wires up (sort, search, filter, paginate, bulk
 * select) already works as a plain link/form GET without this file at all
 * (the batch's "works with no JS" requirement); this only swaps in an Ajax
 * fetch + client-side row re-render instead of a full page reload, and
 * keeps the URL in sync via history.pushState so the result stays
 * shareable/bookmarkable either way.
 *
 * AdminTable::response() returns JSON (not HTML) for an Ajax request, so
 * rows are rendered here from that JSON - see renderRows(). Each column
 * carries an `html` flag (server-computed from whether it has a `format`
 * closure) telling us whether its value is developer-authored safe HTML
 * (insert via .html()) or plain data (insert via .text(), so it can never
 * execute as markup). Same trust model for each row action's `icon_html` -
 * server-rendered via Blade::render('<x-ui.icon .../>', ...), never built
 * from request-controlled input.
 */

const SEARCH_DEBOUNCE_MS = 350;

function fetchTable($root, url) {
    const $tbody = $root.find('[data-table-body]');
    $tbody.attr('aria-busy', 'true');

    return Ajax.request({
        url,
        method: 'GET',
        key: 'admin-table:' + $root.data('table-url'),
        headers: { Accept: 'application/json' },
    })
        .done((data) => {
            render($root, data, url);
            window.history.pushState({}, '', url);
        })
        .always(() => $tbody.attr('aria-busy', 'false'));
}

function render($root, data, url) {
    const hasBulk = data.bulkActions && data.bulkActions.length > 0;
    const hasActions = data.rows.some((row) => row._actions && row._actions.length > 0);

    renderRows($root.find('[data-table-body]'), data.rows, data.columns, hasBulk, hasActions);
    renderSortIndicators($root, data.sort);
    renderCount($root, data.pagination);
    // `url` (the one just fetched), not window.location.href - this runs
    // before the pushState() below, so location is still the PREVIOUS page.
    renderPagination($root, data.pagination, url);
    clearSelection($root);
}

function renderRows($tbody, rows, columns, hasBulk, hasActions) {
    $tbody.empty();

    rows.forEach((row) => {
        const $tr = $('<tr>').attr('data-row-id', row.id);

        if (hasBulk) {
            $tr.append(
                $('<td>', { class: 'ps-4 py-3' }).append(
                    $('<input>', { type: 'checkbox', 'data-row-select': '' }).val(row.id)
                )
            );
        }

        columns.forEach((column) => {
            const $td = $('<td>', { class: `px-4 py-3 text-${column.align || 'start'} text-ink` });
            const value = row[column.key];

            if (column.html) {
                $td.html(value ?? '');
            } else {
                $td.text(value ?? '');
            }

            $tr.append($td);
        });

        if (hasActions) {
            const $wrap = $('<div>', { class: 'flex items-center justify-end gap-1' });

            (row._actions || []).forEach((action) => {
                // Mirrors x-admin.table.blade.php's own row-action markup
                // (icon_html/label are already server-translated/rendered
                // for this JSON path - see AdminTable::formatRowForJson()).
                const $a = $('<a>', {
                    href: action.url,
                    title: action.label,
                    class: 'rounded-lg p-1.5 text-muted transition-colors duration-150 ease-smooth hover:bg-surface hover:text-ink motion-reduce:transition-none',
                });

                if (action.icon_html) {
                    $a.html(action.icon_html);
                } else {
                    $a.append($('<span>', { class: 'text-xs', text: action.label }));
                }

                // A non-GET action (e.g. delete) needs this so
                // admin/layout.js's confirm handler submits it as a real
                // POST+_method override instead of just navigating the
                // link, which would hit a DELETE-only route with GET.
                if (action.method && action.method.toLowerCase() !== 'get') {
                    $a.attr('data-row-action-method', action.method);
                }

                if (action.confirm) {
                    $a.attr('data-confirm', '').attr('data-confirm-message', Ajax.lang('admin_table_confirm_row_action'));
                }

                $wrap.append($a);
            });

            $tr.append($('<td>', { class: 'px-4 py-3' }).append($wrap));
        }

        $tbody.append($tr);
    });
}

function renderSortIndicators($root, sort) {
    $root.find('[data-sort-link]').each(function () {
        const $link = $(this);
        const isActive = $link.data('sortKey') === sort.key;
        const nextDirection = isActive && sort.direction === 'asc' ? 'desc' : 'asc';

        $link.attr('data-sort-direction', nextDirection);
        $link.find('[data-sort-icon]').toggleClass('rotate-180', isActive && sort.direction === 'asc');
    });
}

function renderCount($root, pagination) {
    const $count = $root.find('[data-table-count]');
    if ($count.length) {
        $count.text(
            Ajax.lang('admin_table_showing', {
                from: pagination.from,
                to: pagination.to,
                total: pagination.total,
            })
        );
    }
}

/**
 * Mirrors x-ui.pagination.blade.php's own page-window algorithm (page 1,
 * last page, and anything within 1 of current) so the Ajax-rendered nav
 * looks identical to the server-rendered first load. Rebuilt from scratch
 * on every response instead of patched in place - after an Ajax page
 * change the current-page highlight and every page link's href need to
 * change, not just the count text next to them.
 */
function paginationPages(currentPage, totalPages) {
    const pages = [];

    for (let page = 1; page <= totalPages; page++) {
        if (page === 1 || page === totalPages || Math.abs(page - currentPage) <= 1) {
            pages.push(page);
        }
    }

    return pages;
}

function pageUrl(baseUrl, page) {
    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.set('page', page);

    return url.toString();
}

function renderPagination($root, pagination, currentUrl) {
    const $container = $root.find('[data-table-pagination]');
    if (!$container.length) return;

    const $nav = $container.find('nav[aria-label]');
    if (!$nav.length) return;

    const { currentPage, lastPage } = pagination;
    const $list = $('<ul>', { class: 'flex items-center gap-1 text-sm' });

    const $prev = $('<a>', {
        href: currentPage > 1 ? pageUrl(currentUrl, currentPage - 1) : '#',
        text: Ajax.lang('admin_table_pagination_previous'),
        class: `flex h-9 items-center justify-center rounded-lg px-3 text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none ${currentPage <= 1 ? 'pointer-events-none opacity-50' : ''}`,
    });
    if (currentPage <= 1) $prev.attr({ 'aria-disabled': 'true', tabindex: '-1' });
    $list.append($('<li>').append($prev));

    let previousPage = null;
    paginationPages(currentPage, lastPage).forEach((page) => {
        if (previousPage !== null && page - previousPage > 1) {
            $list.append($('<li>', { class: 'px-1 text-muted', 'aria-hidden': 'true', html: '&hellip;' }));
        }

        const $li = $('<li>');

        if (page === currentPage) {
            $li.append(
                $('<span>', {
                    'aria-current': 'page',
                    class: 'flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-sm font-medium text-primary-foreground',
                    text: page,
                })
            );
        } else {
            $li.append(
                $('<a>', {
                    href: pageUrl(currentUrl, page),
                    'aria-label': Ajax.lang('admin_table_pagination_go_to_page', { page }),
                    class: 'flex h-9 w-9 items-center justify-center rounded-lg text-sm text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none',
                    text: page,
                })
            );
        }

        $list.append($li);
        previousPage = page;
    });

    const $next = $('<a>', {
        href: currentPage < lastPage ? pageUrl(currentUrl, currentPage + 1) : '#',
        text: Ajax.lang('admin_table_pagination_next'),
        class: `flex h-9 items-center justify-center rounded-lg px-3 text-ink transition-colors duration-150 ease-smooth hover:bg-surface motion-reduce:transition-none ${currentPage >= lastPage ? 'pointer-events-none opacity-50' : ''}`,
    });
    if (currentPage >= lastPage) $next.attr({ 'aria-disabled': 'true', tabindex: '-1' });
    $list.append($('<li>').append($next));

    $nav.empty().append($list);
}

function clearSelection($root) {
    $root.find('[data-select-all], [data-row-select]').prop('checked', false);
    updateBulkBar($root);
}

function updateBulkBar($root) {
    const $selected = $root.find('[data-row-select]:checked');
    const $bar = $root.find('[data-bulk-actions-bar]');

    $bar.attr('hidden', $selected.length === 0);
    $bar.find('[data-selected-count]').text(
        Ajax.lang('admin_table_selected_count', { count: $selected.length }) || String($selected.length)
    );
}

function currentUrlWith(params) {
    const url = new URL(window.location.href);

    Object.entries(params).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, value);
        }
    });

    url.searchParams.delete('page');

    return url.toString();
}

function initSort() {
    $(document).on('click', '[data-admin-table] [data-sort-link]', function (event) {
        event.preventDefault();

        const $root = $(this).closest('[data-admin-table]');
        const url = currentUrlWith({
            sort: $(this).data('sortKey'),
            direction: $(this).data('sortDirection'),
        });

        fetchTable($root, url);
    });
}

function initSearch() {
    let timer = null;

    $(document).on('submit', '[data-admin-table] [data-admin-search]', function (event) {
        event.preventDefault();
        const $root = $(this).closest('[data-admin-table]');
        fetchTable($root, currentUrlWith({ q: $(this).find('[name="q"]').val() }));
    });

    $(document).on('input', '[data-admin-table] [data-admin-search] input[name="q"]', function () {
        const $form = $(this).closest('[data-admin-search]');
        clearTimeout(timer);
        timer = setTimeout(() => $form.trigger('submit'), SEARCH_DEBOUNCE_MS);
    });
}

function initFilters() {
    $(document).on('submit', '[data-admin-table] [data-admin-filters]', function (event) {
        event.preventDefault();
        const $root = $(this).closest('[data-admin-table]');
        fetchTable($root, $(this).attr('action') || currentUrlWith(Object.fromEntries(new FormData(this))));
    });
}

function initPagination() {
    $(document).on('click', '[data-admin-table] nav[aria-label] a', function (event) {
        if ($(this).attr('aria-disabled') === 'true') return;

        event.preventDefault();
        const $root = $(this).closest('[data-admin-table]');
        fetchTable($root, $(this).attr('href'));
    });
}

function initBulkSelection() {
    $(document).on('change', '[data-admin-table] [data-select-all]', function () {
        const $root = $(this).closest('[data-admin-table]');
        $root.find('[data-row-select]').prop('checked', $(this).is(':checked'));
        updateBulkBar($root);
    });

    $(document).on('change', '[data-admin-table] [data-row-select]', function () {
        updateBulkBar($(this).closest('[data-admin-table]'));
    });
}

function runBulkAction($root, action) {
    const ids = $root
        .find('[data-row-select]:checked')
        .map(function () {
            return $(this).val();
        })
        .get();

    Ajax.request({
        url: $root.data('table-url').replace(/\/?$/, '/bulk-action'),
        method: 'POST',
        data: { ids, action },
    }).done(() => fetchTable($root, $root.data('table-url')));
}

function initBulkActions() {
    $(document).on('click', '[data-admin-table] [data-bulk-action]', function (event) {
        const $button = $(this);
        const $root = $button.closest('[data-admin-table]');
        const action = $button.data('bulkAction');

        if ($button.is('[data-confirm]')) {
            event.preventDefault();
            window.Dersey?.confirmAction?.($button.data('confirmMessage'), () => runBulkAction($root, action));
            return;
        }

        runBulkAction($root, action);
    });
}

function init() {
    if (!$('[data-admin-table]').length) return;

    initSort();
    initSearch();
    initFilters();
    initPagination();
    initBulkSelection();
    initBulkActions();
}

export default { init };
