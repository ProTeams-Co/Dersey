{{--
    A single shared dialog, mounted once in layouts/admin.blade.php - any
    [data-confirm] trigger (row delete link, bulk-action button) opens this
    instead of each action rendering its own modal. admin/table.js reads
    the trigger's data-confirm-message and wires the accept button to
    either submit the pending form or continue the pending fetch.
--}}
@props(['id' => 'admin-confirm-dialog'])

<div id="{{ $id }}" data-modal hidden class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div data-action="modal-close" class="fixed inset-0 bg-ink/40"></div>

    <div
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        class="relative w-full max-w-sm rounded-xl bg-canvas p-6 shadow-lg"
    >
        <h2 id="{{ $id }}-title" data-confirm-title class="text-base font-semibold text-ink">
            {{ __('admin.table.confirm_bulk_action') }}
        </h2>

        <p data-confirm-message class="mt-2 text-sm text-muted"></p>

        <div class="mt-6 flex justify-end gap-2">
            <x-ui.button variant="outline" data-action="modal-close">{{ __('common.cancel') }}</x-ui.button>
            <x-ui.button variant="danger" data-confirm-accept>{{ __('common.confirm') }}</x-ui.button>
        </div>
    </div>
</div>
