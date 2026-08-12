import $ from 'jquery';
import Ajax from '../core/ajax';

/**
 * Drag-and-drop tree reordering (Task 1's own requirement) - SortableJS is
 * only dynamically imported when [data-category-tree] actually exists on
 * the page (the tree view specifically, not the search-mode AdminTable
 * fallback), same "don't ship weight nothing else needs" reasoning as
 * FilePond/CKEditor 5 in admin/media.js and admin/editor.js.
 *
 * Every [data-sortable-level] <ul> (one per tree level - see
 * admin/categories/_tree-node.blade.php) shares the same SortableJS
 * `group` name, which is what lets a drag cross between levels (i.e.
 * re-parent a category), not just reorder within one. On drop, the
 * moved node's new parent (the target list's data-parent-id, blank for
 * root) and its new "before" sibling (the very next <li> in the list, if
 * any) are sent to CategoriesController::reorder() - kalnoy/nestedset's
 * insertBeforeNode()/appendToNode() figure out the rest server-side.
 */

async function initTree(root) {
    const { default: Sortable } = await import('sortablejs');

    const urlTemplate = $(root).data('reorderUrlTemplate');

    root.querySelectorAll('[data-sortable-level]').forEach((list) => {
        Sortable.create(list, {
            group: 'admin-category-tree',
            handle: '[data-tree-drag-handle]',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onEnd(event) {
                const $item = $(event.item);
                const categoryId = $item.data('categoryId');
                const $newList = $(event.to);
                const parentId = $newList.data('parentId') || null;
                const $nextSibling = $item.next('[data-category-node]');
                const beforeId = $nextSibling.length ? $nextSibling.data('categoryId') : null;

                Ajax.request({
                    url: urlTemplate.replace('__ID__', categoryId),
                    method: 'PATCH',
                    data: { parent_id: parentId, before_id: beforeId },
                }).fail(() => window.location.reload());
            },
        });
    });
}

function initToggle() {
    $(document).on('click', '[data-category-tree] [data-tree-toggle]', function () {
        const $icon = $(this);
        const $branch = $icon.closest('[data-category-node]').find('> [data-sortable-level]');

        $branch.toggleClass('hidden');
        $icon.find('svg').toggleClass('rotate-180');
    });
}

function init() {
    const root = document.querySelector('[data-category-tree]');
    if (!root) return;

    initTree(root);
    initToggle();
}

export default { init };
