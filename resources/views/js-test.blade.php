@extends('layouts.app')

@section('content')
    <div class="bg-canvas text-ink min-h-screen p-6 md:p-10">
        <div class="container">
            <header class="mb-10 border-b border-line pb-6">
                <h1 class="text-3xl">JS Infrastructure — Batch 1.4</h1>
                <p class="text-muted text-sm mt-1">Temporary verification page — removed in Batch 1.6.</p>
            </header>

            {{-- ============================================================
                 Ajax error handling — each button hits a real endpoint that
                 deliberately returns that status code (routes/ajax.php),
                 not a mocked/simulated response.
            ============================================================ --}}
            <section class="mb-14">
                <h2 class="text-xl mb-4">Ajax error handling</h2>
                <div class="flex flex-wrap gap-3">
                    <button type="button" data-action="test-ajax" data-url="{{ route('ajax.test.419') }}" data-method="POST" class="px-3 py-1.5 rounded-md border border-border-interactive">419 (session expired)</button>
                    <button type="button" data-action="test-ajax" data-url="{{ route('ajax.test.422') }}" data-method="POST" class="px-3 py-1.5 rounded-md border border-border-interactive">422 (validation)</button>
                    <button type="button" data-action="test-ajax" data-url="{{ route('ajax.test.429') }}" data-method="POST" class="px-3 py-1.5 rounded-md border border-border-interactive">429 (rate limited)</button>
                    <button type="button" data-action="test-ajax" data-url="{{ route('ajax.test.500') }}" data-method="POST" class="px-3 py-1.5 rounded-md border border-border-interactive">500 (server error)</button>
                    <button type="button" data-action="test-timeout" class="px-3 py-1.5 rounded-md border border-border-interactive">Network / timeout</button>
                </div>
                <p class="text-muted text-xs mt-3">419 refreshes the CSRF token and retries once automatically — it fails again on purpose (the test route always returns 419), so the expected result is: one retry, then a toast + page reload.</p>
            </section>

            {{-- ============================================================
                 Toasts
            ============================================================ --}}
            <section class="mb-14">
                <h2 class="text-xl mb-4">Toasts</h2>
                <div class="flex flex-wrap gap-3">
                    <button type="button" data-action="test-toast" data-type="success" class="px-3 py-1.5 rounded-md border border-border-interactive">success</button>
                    <button type="button" data-action="test-toast" data-type="error" class="px-3 py-1.5 rounded-md border border-border-interactive">error</button>
                    <button type="button" data-action="test-toast" data-type="warning" class="px-3 py-1.5 rounded-md border border-border-interactive">warning</button>
                    <button type="button" data-action="test-toast" data-type="info" class="px-3 py-1.5 rounded-md border border-border-interactive">info</button>
                    <button type="button" data-action="test-toast-flood" class="px-3 py-1.5 rounded-md border border-border-interactive">fire 5 toasts (max-visible check)</button>
                </div>
            </section>

            {{-- ============================================================
                 Validation form — real form.js wiring, not simulated. The
                 test endpoint always rejects "email" so the error should
                 render under that field automatically.
            ============================================================ --}}
            <section class="mb-14">
                <h2 class="text-xl mb-4">Validation form (422 → field errors)</h2>
                {{-- novalidate: this is an ajax-submitted form — the server's
                     422 response is the single source of truth for validation,
                     not a mix of browser-native and server validation UI. --}}
                <form data-ajax-form action="{{ route('ajax.test.422') }}" method="POST" novalidate class="max-w-sm">
                    <label for="js-test-email" class="block text-sm mb-1">Email</label>
                    <input id="js-test-email" name="email" type="email" class="w-full rounded-md border border-border-interactive px-3 py-1.5">
                    <button type="submit" class="mt-3 px-3 py-1.5 rounded-md border border-border-interactive">Submit</button>
                </form>
            </section>

            {{-- ============================================================
                 Duplicate-request cancellation — every keystroke fires a
                 request sharing the same dedupe key against the (real,
                 fast) csrf-token endpoint. Typing quickly should abort every
                 request except the last one; the log below makes that
                 externally observable instead of just "trust me".
            ============================================================ --}}
            <section class="mb-14">
                <h2 class="text-xl mb-4">Duplicate request cancellation</h2>
                <input type="text" id="js-test-search" placeholder="Type fast…" class="w-full max-w-sm rounded-md border border-border-interactive px-3 py-1.5">
                <ul id="js-test-log" class="mt-3 text-xs font-mono text-muted space-y-1 max-h-48 overflow-y-auto"></ul>
            </section>

            {{-- ============================================================
                 prefers-reduced-motion
            ============================================================ --}}
            <section class="mb-14">
                <h2 class="text-xl mb-4">Motion</h2>
                <p class="text-sm">
                    <code>Dersey.motion.enabled</code>:
                    <strong id="js-test-motion-status">—</strong>
                    — toggle "Reduce motion" in your OS accessibility settings and reload to see this flip.
                </p>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        $(function () {
            const log = (message) => {
                const $item = $('<li>').text(`${new Date().toLocaleTimeString()} — ${message}`);
                $('#js-test-log').prepend($item);
            };

            $('#js-test-motion-status').text(String(window.Dersey.motion?.enabled));

            $(document).on('click', '[data-action="test-ajax"]', function () {
                const $btn = $(this);
                Dersey.ajax.request({
                    url: $btn.data('url'),
                    method: $btn.data('method'),
                    $trigger: $btn,
                });
            });

            $(document).on('click', '[data-action="test-timeout"]', function () {
                // A URL nothing answers — jQuery's own timeout option turns
                // this into the same 0/timeout path a real dead connection
                // would hit, without needing a route that hangs forever.
                Dersey.ajax.request({
                    url: 'https://10.255.255.1/unreachable',
                    method: 'GET',
                    timeout: 2000,
                });
            });

            $(document).on('click', '[data-action="test-toast"]', function () {
                const type = $(this).data('type');
                Dersey.toast[type](`Test ${type} toast.`);
            });

            $(document).on('click', '[data-action="test-toast-flood"]', function () {
                for (let i = 1; i <= 5; i += 1) {
                    Dersey.toast.info(`Toast #${i}`);
                }
            });

            $('#js-test-search').on('input', function () {
                const value = $(this).val();
                const requestId = value || '(empty)';
                log(`request started: "${requestId}"`);

                Dersey.ajax.request({
                    url: Dersey.routes.csrfToken,
                    method: 'GET',
                    key: 'js-test-search',
                }).done(() => log(`request completed: "${requestId}"`));
            });
        });
    </script>
@endpush
