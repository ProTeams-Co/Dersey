import $ from 'jquery';

/**
 * Generic add/remove/drag-reorder behavior for any [data-repeater] block
 * (x-admin.repeater's own markup contract - see that component's docblock).
 * Batch 3.1 gap: the component existed since 3.0 but this JS behavior never
 * did - Task 3 (attribute values) is its first real consumer.
 *
 * - "Add": clones the <template data-repeater-template> content, replaces
 *   every literal "__INDEX__" placeholder (used in every field's name=) with
 *   a fresh, never-reused index, and appends it.
 * - "Remove" on a brand-new (never-saved) row: just removes the DOM node -
 *   nothing to tell the server about.
 * - "Remove" on an already-saved row (has a hidden values[i][id] input):
 *   can't just vanish from the DOM - the id still has to be submitted with
 *   a delete flag for the server to actually delete it. Toggles a hidden
 *   [data-repeater-delete] input between "0"/"1" and visually marks the row,
 *   with the same click undoing it.
 * - Drag-reorder: SortableJS, dynamically imported only for a
 *   [data-repeater-rows][data-repeater-sortable] list - same "don't ship
 *   weight nothing else needs" reasoning as category-tree.js. Renumbers
 *   each row's [data-repeater-sort-input] on drop so submitted `sort`
 *   values match the new visual order without a separate reorder request.
 */

let nextIndex = Date.now();

function addRow($repeater) {
    const $template = $repeater.find('> [data-repeater-template]');
    if (!$template.length) return;

    const index = nextIndex++;
    const html = $template.html().replaceAll('__INDEX__', String(index));

    $repeater.find('> [data-repeater-rows]').append(html);
}

function toggleRemove($row) {
    const $deleteInput = $row.find('[data-repeater-delete]');

    if (!$deleteInput.length) {
        $row.remove();
        return;
    }

    const isMarked = $deleteInput.val() === '1';
    $deleteInput.val(isMarked ? '0' : '1');
    $row.toggleClass('opacity-40 pointer-events-none', !isMarked);
}

async function initSortable() {
    const lists = document.querySelectorAll('[data-repeater-rows][data-repeater-sortable]');
    if (!lists.length) return;

    const { default: Sortable } = await import('sortablejs');

    lists.forEach((list) => {
        Sortable.create(list, {
            handle: '[data-repeater-drag-handle]',
            animation: 150,
            onEnd() {
                $(list)
                    .children('[data-repeater-row]')
                    .each(function (index) {
                        $(this).find('[data-repeater-sort-input]').val(index);
                    });
            },
        });
    });
}

function init() {
    initSortable();

    $(document).on('click', '[data-repeater-add]', function () {
        addRow($(this).closest('[data-repeater]'));
    });

    $(document).on('click', '[data-repeater-remove]', function () {
        toggleRemove($(this).closest('[data-repeater-row]'));
    });
}

export default { init };
