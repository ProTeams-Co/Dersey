@props(['title' => null, 'description' => null])

<x-ui.empty-state
    :title="$title ?? __('admin.table.no_results_title')"
    :description="$description ?? __('admin.table.no_results_description')"
    {{ $attributes }}
>
    @isset($icon)
        <x-slot:icon>{{ $icon }}</x-slot:icon>
    @else
        <x-slot:icon><x-ui.icon name="inbox" class="h-7 w-7" /></x-slot:icon>
    @endisset

    @isset($action)
        <x-slot:action>{{ $action }}</x-slot:action>
    @endisset
</x-ui.empty-state>
