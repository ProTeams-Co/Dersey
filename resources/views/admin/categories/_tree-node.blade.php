@php
    $translation = $category->translate('ar');
    $blockers = $category->deletionBlockersFor(
        $category->children->isNotEmpty(),
        $directProductCategoryIds->contains($category->id),
    );
    $canDelete = $blockers === [];
@endphp

<li data-category-id="{{ $category->id }}" data-category-node>
    <div class="flex items-center gap-2 rounded-lg border border-line bg-canvas px-3 py-2.5" style="margin-inline-start: {{ $depth * 1.5 }}rem">
        <span data-tree-drag-handle class="cursor-grab text-muted" title="{{ __('admin.categories.drag_handle') }}">
            <x-ui.icon name="menu" class="h-4 w-4" />
        </span>

        @if ($category->children->isNotEmpty())
            <button type="button" data-tree-toggle class="text-muted" aria-label="{{ __('admin.categories.toggle_branch') }}">
                <x-ui.icon name="chevron-down" class="h-4 w-4" />
            </button>
        @else
            <span class="inline-block h-4 w-4" aria-hidden="true"></span>
        @endif

        @if ($category->image)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($category->image) }}" alt="" class="h-6 w-6 shrink-0 rounded object-cover">
        @else
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-surface text-xs text-muted">—</span>
        @endif

        <span class="flex-1 truncate text-sm text-ink">{{ $translation?->name }}</span>

        <span class="shrink-0 text-xs text-muted" title="{{ __('admin.categories.column_products_count') }}">
            {{ $productCounts->get($category->id, 0) }}
        </span>

        <span class="shrink-0">
            @if ($category->is_active)
                <span class="inline-flex items-center rounded-full bg-success px-2 py-0.5 text-xs font-medium text-success-foreground">{{ __('admin.common.yes') }}</span>
            @else
                <span class="inline-flex items-center rounded-full bg-neutral-200 px-2 py-0.5 text-xs font-medium text-ink">{{ __('admin.common.no') }}</span>
            @endif
        </span>

        <div class="flex shrink-0 items-center gap-1">
            <a href="{{ route('admin.categories.edit', $category->id) }}" class="rounded-lg p-1.5 text-muted transition-colors duration-150 ease-smooth hover:bg-surface hover:text-ink motion-reduce:transition-none" title="{{ __('admin.table.actions_edit') }}">
                <x-ui.icon name="pencil" class="h-4 w-4" />
            </a>

            @if ($canDelete)
                <a
                    href="{{ route('admin.categories.destroy', $category->id) }}"
                    data-row-action-method="delete"
                    data-confirm
                    data-confirm-message="{{ __('admin.table.confirm_bulk_action') }}"
                    class="rounded-lg p-1.5 text-muted transition-colors duration-150 ease-smooth hover:bg-surface hover:text-danger motion-reduce:transition-none"
                    title="{{ __('admin.table.actions_delete') }}"
                >
                    <x-ui.icon name="trash" class="h-4 w-4" />
                </a>
            @else
                <span
                    class="cursor-not-allowed rounded-lg p-1.5 text-muted opacity-40"
                    title="{{ __('admin.table.delete_disabled_tooltip', ['reason' => implode(', ', array_map('__', $blockers))]) }}"
                >
                    <x-ui.icon name="trash" class="h-4 w-4" />
                </span>
            @endif
        </div>
    </div>

    @if ($category->children->isNotEmpty())
        <ul data-sortable-level data-parent-id="{{ $category->id }}" class="mt-1 space-y-1">
            @foreach ($category->children as $child)
                @include('admin.categories._tree-node', ['category' => $child, 'depth' => $depth + 1, 'productCounts' => $productCounts, 'directProductCategoryIds' => $directProductCategoryIds])
            @endforeach
        </ul>
    @endif
</li>
