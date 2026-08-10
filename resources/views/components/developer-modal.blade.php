{{--
    Self-contained: trigger button + the modal it opens. Open/close, Escape,
    and backdrop-click all come free from core/modal.js's existing
    [data-action="modal-open|modal-close"] / [data-modal] delegation
    (already initialized by app.js on every page) — nothing new to wire up
    for those. Focus trap isn't part of that shared module, so it's added
    below as a small script scoped to just this modal, listening to the
    modal:opened/modal:closed events core/modal.js already emits.
--}}
<button
    id="developer-modal-trigger"
    type="button"
    data-action="modal-open"
    data-modal-target="#developer-modal"
    class="inline-flex items-center gap-2 rounded-full bg-primary px-6 py-3 text-primary-foreground font-medium shadow-md transition-shadow duration-200 ease-smooth hover:shadow-lg motion-reduce:transition-none"
>
    {{ __('welcome.developers.button') }}
</button>

<div
    id="developer-modal"
    data-modal
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="developer-modal-heading"
    class="fixed inset-0 z-modal flex items-center justify-center bg-neutral-950/50 p-4 transition-opacity duration-200 ease-smooth starting:opacity-0 motion-reduce:transition-none"
>
    <div class="relative w-full max-w-sm rounded-2xl bg-surface p-6 shadow-xl transition-all duration-200 ease-smooth starting:scale-95 starting:opacity-0 motion-reduce:transition-none md:p-8">
        <button
            type="button"
            data-action="modal-close"
            aria-label="{{ __('welcome.developers.modal_close') }}"
            class="absolute end-4 top-4 text-muted transition-colors duration-200 ease-smooth hover:text-ink motion-reduce:transition-none"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-5 w-5" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>

        <h2 id="developer-modal-heading" class="text-xl font-semibold text-ink">
            {{ __('welcome.developers.modal_heading') }}
        </h2>

        <div class="mt-6 flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground font-bold" aria-hidden="true">
                {{ \Illuminate\Support\Str::substr(__('welcome.developers.name'), 0, 1) }}
            </div>
            <div>
                <p class="font-medium text-ink">{{ __('welcome.developers.name') }}</p>
                <p class="text-sm text-muted">{{ __('welcome.developers.role') }}</p>
            </div>
        </div>

        <a
            href="https://github.com/Eng-AbdallahEmad"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-6 inline-flex items-center gap-2 rounded-lg border border-border-interactive px-4 py-2 text-sm text-ink transition-colors duration-200 ease-smooth hover:bg-canvas motion-reduce:transition-none"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
            </svg>
            {{ __('welcome.developers.github_label') }}
        </a>
    </div>
</div>

@push('scripts')
    {{--
        type="module" is required here, not decorative — app.js is loaded as
        a deferred module script in <head>, and a plain (non-module) script
        placed later in the body still runs the instant the parser reaches
        it, which is BEFORE any deferred script finishes, including the one
        that sets window.$. Making this a module too puts it in the same
        deferred queue, so document order (this after app.js) decides
        execution order instead.
    --}}
    <script type="module">
        $(function () {
            var $modal = $('#developer-modal');
            var $trigger = $('#developer-modal-trigger');
            var focusableSelector = 'a[href], button:not([disabled])';

            function trapFocus(event) {
                if (event.key !== 'Tab') return;

                var $focusable = $modal.find(focusableSelector).filter(':visible');
                if (!$focusable.length) return;

                var first = $focusable.get(0);
                var last = $focusable.get($focusable.length - 1);

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }

            window.Dersey.events.on('modal:opened', function (event, $opened) {
                if (!$opened.is($modal)) return;
                $modal.find(focusableSelector).first().trigger('focus');
                $(document).on('keydown.developerModalTrap', trapFocus);
            });

            window.Dersey.events.on('modal:closed', function (event, $closed) {
                if (!$closed.is($modal)) return;
                $(document).off('keydown.developerModalTrap');
                $trigger.trigger('focus');
            });
        });
    </script>
@endpush
