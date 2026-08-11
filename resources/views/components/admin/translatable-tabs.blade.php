{{--
    Self-contained (doesn't depend on x-ui.tabs being on the same page) -
    same data-tabs/data-tab-trigger/data-tab-panel contract as x-ui.tabs,
    plus a missing-fields dot on any tab whose language has incomplete
    required content, since forgetting to fill a whole language is the one
    mistake this component exists to make visible (CLAUDE.md-adjacent:
    every catalog screen has this problem, not just one).

    Caller renders one panel per locale as the default slot, matching:
      <div id="{{ $id }}-panel-{key}" data-tab-panel="{key}" ...>
--}}
@props(['locales', 'missing' => [], 'id' => null])

@php
    $id = $id ?? 'i18n-tabs-'.\Illuminate\Support\Str::random(8);
    $activeKey = array_key_first($locales);
@endphp

<div data-tabs="{{ $id }}" data-translatable-tabs="{{ $id }}">
    <div role="tablist" aria-label="{{ __('admin.form.language_tabs') }}" class="flex gap-1 border-b border-line">
        @foreach ($locales as $key => $label)
            <button
                type="button"
                role="tab"
                id="{{ $id }}-tab-{{ $key }}"
                aria-controls="{{ $id }}-panel-{{ $key }}"
                aria-selected="{{ $key === $activeKey ? 'true' : 'false' }}"
                tabindex="{{ $key === $activeKey ? '0' : '-1' }}"
                data-tab-trigger="{{ $key }}"
                class="flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors duration-150 ease-smooth hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary motion-reduce:transition-none {{ $key === $activeKey ? 'border-primary text-primary' : 'border-transparent text-muted' }}"
            >
                {{ $label }}

                @if (! empty($missing[$key]))
                    <span
                        data-tab-missing-indicator
                        class="h-1.5 w-1.5 shrink-0 rounded-full bg-danger"
                        role="img"
                        aria-label="{{ __('admin.form.language_incomplete') }}"
                        title="{{ __('admin.form.language_incomplete') }}"
                    ></span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="pt-4">
        {{ $slot }}
    </div>
</div>

@once
    @push('scripts')
        <script type="module">
            $(function () {
                function activateTab($trigger) {
                    var $tabsRoot = $trigger.closest('[data-tabs]');
                    var key = $trigger.data('tabTrigger');

                    $tabsRoot.find('[data-tab-trigger]').each(function () {
                        var isActive = String($(this).data('tabTrigger')) === String(key);
                        $(this)
                            .attr('aria-selected', isActive ? 'true' : 'false')
                            .attr('tabindex', isActive ? '0' : '-1')
                            .toggleClass('border-primary text-primary', isActive)
                            .toggleClass('border-transparent text-muted', !isActive);
                    });

                    $tabsRoot.find('[data-tab-panel]').each(function () {
                        $(this).attr('hidden', String($(this).data('tabPanel')) !== String(key));
                    });
                }

                $(document).on('click', '[data-translatable-tabs] [data-tab-trigger]', function () {
                    activateTab($(this));
                });

                window.Dersey = window.Dersey || {};
                window.Dersey.activateTranslatableTab = function ($tabsRoot, key) {
                    activateTab($tabsRoot.find('[data-tab-trigger="' + key + '"]'));
                };
            });
        </script>
    @endpush
@endonce
