@props([
    'tabs' => [],
    'id' => null,
])

@php
    $id = $id ?? 'tabs-'.\Illuminate\Support\Str::random(8);
    $activeKey = array_key_first($tabs);
@endphp

{{--
    Panels are free-form slot content, not prop-driven — the caller writes
    each one directly, matching this exact contract so the JS below (and
    the aria wiring above) can find them:
      <div id="{{ $id }}-panel-{key}" role="tabpanel" data-tab-panel="{key}"
           aria-labelledby="{{ $id }}-tab-{key}" tabindex="0" [hidden unless it's the first tab]>
--}}
<div data-tabs="{{ $id }}">
    <div role="tablist" aria-label="{{ __('components.tabs.nav_label') }}" class="flex gap-1 border-b border-line">
        @foreach ($tabs as $key => $label)
            <button
                type="button"
                role="tab"
                id="{{ $id }}-tab-{{ $key }}"
                aria-controls="{{ $id }}-panel-{{ $key }}"
                aria-selected="{{ $key === $activeKey ? 'true' : 'false' }}"
                tabindex="{{ $key === $activeKey ? '0' : '-1' }}"
                data-tab-trigger="{{ $key }}"
                class="border-b-2 px-4 py-2.5 text-sm font-medium transition-colors duration-150 ease-smooth hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary motion-reduce:transition-none {{ $key === $activeKey ? 'border-primary text-primary' : 'border-transparent text-muted' }}"
            >
                {{ $label }}
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

                $(document).on('click', '[data-tab-trigger]', function () {
                    activateTab($(this));
                });

                // Arrow keys follow reading direction (WAI-ARIA tabs pattern):
                // in rtl, "forward" through the tablist is visually leftward.
                $(document).on('keydown', '[data-tab-trigger]', function (event) {
                    var navigationKeys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];
                    if (navigationKeys.indexOf(event.key) === -1) return;
                    event.preventDefault();

                    var $tablist = $(this).closest('[role="tablist"]');
                    var $allTabs = $tablist.find('[data-tab-trigger]');
                    var currentIndex = $allTabs.index(this);
                    var isRtl = document.documentElement.dir === 'rtl';
                    var forwardKey = isRtl ? 'ArrowLeft' : 'ArrowRight';
                    var backwardKey = isRtl ? 'ArrowRight' : 'ArrowLeft';

                    var nextIndex = currentIndex;
                    if (event.key === forwardKey) nextIndex = (currentIndex + 1) % $allTabs.length;
                    else if (event.key === backwardKey) nextIndex = (currentIndex - 1 + $allTabs.length) % $allTabs.length;
                    else if (event.key === 'Home') nextIndex = 0;
                    else if (event.key === 'End') nextIndex = $allTabs.length - 1;

                    var $next = $allTabs.eq(nextIndex);
                    activateTab($next);
                    $next.trigger('focus');
                });
            });
        </script>
    @endpush
@endonce
