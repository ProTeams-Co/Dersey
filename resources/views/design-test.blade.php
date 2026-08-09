<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ request('dir', 'rtl') === 'ltr' ? 'ltr' : 'rtl' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Design Tokens — Batch 1.2</title>

        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-canvas text-ink p-6 md:p-10">
        <div class="container">
            <header class="mb-10 flex items-center justify-between gap-4 border-b border-line pb-6">
                <div>
                    <h1 class="text-3xl">Design Tokens — Batch 1.2</h1>
                    <p class="text-muted text-sm mt-1">Temporary verification page — removed in Batch 1.6.</p>

                    {{--
                        TODO(1.6): hardcoded English text below (alt attributes,
                        copyright line, credit) — normally forbidden (CLAUDE.md
                        section 11), accepted here only because this whole page
                        is temporary and is deleted in this same batch. Do not
                        copy this pattern into any page that survives past 1.6.
                    --}}
                    <div class="mt-4 flex items-center gap-4">
                        <img src="{{ asset('assets/logos/logo-green.svg') }}" alt="Dersey" width="50" height="32" class="h-8 w-auto">
                        <span class="text-muted text-lg" aria-hidden="true">&times;</span>
                        <img src="{{ asset('assets/logos/logo-proteamsco-black.png') }}" alt="ProTeamsCo" width="102" height="32" class="h-8 w-auto">
                    </div>

                    <p class="text-muted text-xs mt-3">
                        &copy; {{ date('Y') }} ProTeamsCo. All rights reserved.<br>
                        Developed by
                        <a href="https://github.com/Eng-AbdallahEmad" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2 hover:text-ink">Abdallah Emad Khalifa</a>.
                    </p>
                </div>
                <nav class="flex gap-3 text-sm">
                    <a href="?dir=rtl" class="px-3 py-1.5 rounded-md border border-border-interactive">RTL</a>
                    <a href="?dir=ltr" class="px-3 py-1.5 rounded-md border border-border-interactive">LTR</a>
                </nav>
            </header>

            {{-- ============================================================
                 Fonts
            ============================================================ --}}
            <section class="mb-14">
                <h2 class="text-xl mb-4">Fonts</h2>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="p-5 rounded-lg bg-surface border border-line">
                        <p class="text-xs text-muted mb-2">Heading / EN — Clash Display</p>
                        <p style="font-family: 'Clash Display', sans-serif; font-weight: 400;" class="text-2xl">The quick brown fox 0123</p>
                        <p style="font-family: 'Clash Display', sans-serif; font-weight: 500;" class="text-2xl">The quick brown fox 0123</p>
                        <p style="font-family: 'Clash Display', sans-serif; font-weight: 600;" class="text-2xl">The quick brown fox 0123</p>
                        <p style="font-family: 'Clash Display', sans-serif; font-weight: 700;" class="text-2xl">The quick brown fox 0123</p>
                    </div>

                    <div class="p-5 rounded-lg bg-surface border border-line">
                        <p class="text-xs text-muted mb-2">Heading / AR — Alexandria</p>
                        <p style="font-family: 'Alexandria', sans-serif; font-weight: 400;" class="text-2xl">الثعلب البني السريع ٠١٢٣</p>
                        <p style="font-family: 'Alexandria', sans-serif; font-weight: 500;" class="text-2xl">الثعلب البني السريع ٠١٢٣</p>
                        <p style="font-family: 'Alexandria', sans-serif; font-weight: 600;" class="text-2xl">الثعلب البني السريع ٠١٢٣</p>
                        <p style="font-family: 'Alexandria', sans-serif; font-weight: 700;" class="text-2xl">الثعلب البني السريع ٠١٢٣</p>
                    </div>

                    <div class="p-5 rounded-lg bg-surface border border-line">
                        <p class="text-xs text-muted mb-2">Body / EN — Satoshi</p>
                        <p style="font-family: 'Satoshi', sans-serif; font-weight: 400;" class="text-lg">The quick brown fox jumps over 0123</p>
                        <p style="font-family: 'Satoshi', sans-serif; font-weight: 500;" class="text-lg">The quick brown fox jumps over 0123</p>
                        <p style="font-family: 'Satoshi', sans-serif; font-weight: 600;" class="text-lg">The quick brown fox jumps over 0123 (no 600 face registered — CSS font matching resolves this to 700)</p>
                        <p style="font-family: 'Satoshi', sans-serif; font-weight: 700;" class="text-lg">The quick brown fox jumps over 0123</p>
                    </div>

                    <div class="p-5 rounded-lg bg-surface border border-line">
                        <p class="text-xs text-muted mb-2">Body / AR — IBM Plex Sans Arabic</p>
                        <p style="font-family: 'IBM Plex Sans Arabic', sans-serif; font-weight: 400;" class="text-lg">الثعلب البني السريع يقفز فوق ٠١٢٣</p>
                        <p style="font-family: 'IBM Plex Sans Arabic', sans-serif; font-weight: 500;" class="text-lg">الثعلب البني السريع يقفز فوق ٠١٢٣</p>
                        <p style="font-family: 'IBM Plex Sans Arabic', sans-serif; font-weight: 600;" class="text-lg">الثعلب البني السريع يقفز فوق ٠١٢٣</p>
                        <p style="font-family: 'IBM Plex Sans Arabic', sans-serif; font-weight: 700;" class="text-lg">الثعلب البني السريع يقفز فوق ٠١٢٣</p>
                    </div>
                </div>

                {{-- Connection-shaping test — subsetting kept init/medi/fina/isol on
                     purpose; this sentence has letters joining from both sides, plus
                     Arabic-Indic digits, Latin digits, and Arabic punctuation. --}}
                <div class="mt-6 p-5 rounded-lg bg-surface border border-line">
                    <p class="text-xs text-muted mb-2">Connection shaping + digits + punctuation (post-subsetting check)</p>
                    <p style="font-family: 'Alexandria', sans-serif; font-weight: 600;" class="text-2xl mb-2">الأزياء العصرية للمرأة العربية</p>
                    <p style="font-family: 'IBM Plex Sans Arabic', sans-serif; font-weight: 400;" class="text-lg mb-2">الأزياء العصرية للمرأة العربية</p>
                    <p style="font-family: 'IBM Plex Sans Arabic', sans-serif; font-weight: 400;" class="text-lg">
                        الأرقام العربية: ٠١٢٣٤٥٦٧٨٩ — English digits: 0123456789 — الترقيم: أهلًا، مرحبًا؛ هل تسأل؟ نعم!
                    </p>
                </div>

                <p class="text-xs text-muted mt-4">
                    English-only text above should NOT trigger an Arabic font download — Alexandria/IBM Plex Sans
                    Arabic are self-hosted (see fonts-arabic.css) and only requested because this page also renders
                    Arabic text. Check the Network tab on a page with English content only to confirm unicode-range
                    is doing its job for Clash Display/Satoshi.
                </p>
            </section>

            {{-- ============================================================
                 Neutral scale
            ============================================================ --}}
            <section class="mb-14">
                <h2 class="text-xl mb-4">Neutral scale</h2>
                <div class="grid grid-cols-6 sm:grid-cols-11 gap-2">
                    @foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950] as $step)
                        <div class="rounded-md overflow-hidden border border-line">
                            <div class="h-16 bg-neutral-{{ $step }}"></div>
                            <div class="text-xs text-center py-1 bg-surface">{{ $step }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ============================================================
                 Semantic colors
            ============================================================ --}}
            <section class="mb-14">
                <h2 class="text-xl mb-4">Semantic colors</h2>

                @foreach (['primary' => 800, 'accent' => 300, 'success' => 700, 'warning' => 600, 'danger' => 600] as $color => $anchor)
                    <div class="mb-6">
                        <p class="text-sm text-muted mb-2">{{ ucfirst($color) }} <span class="text-xs">(anchor {{ $anchor }})</span></p>
                        <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
                            @foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900] as $step)
                                <div class="rounded-md overflow-hidden border border-line">
                                    <div class="h-14 bg-{{ $color }}-{{ $step }} flex items-end justify-center pb-1">
                                        @if ($step === $anchor)
                                            <span class="text-[10px] bg-canvas rounded px-1">DEFAULT</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-center py-1 bg-surface">{{ $step }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- ============================================================
                 Buttons — every state
            ============================================================ --}}
            <section class="mb-14">
                <h2 class="text-xl mb-4">Button states</h2>

                <div class="grid gap-6 md:grid-cols-2">
                    {{-- primary: white text, hover=lighter, active=darker --}}
                    <div class="p-5 rounded-lg bg-surface border border-line">
                        <p class="text-xs text-muted mb-3">primary (white text; hover 700 lighter, active 900 darker)</p>
                        <div class="flex flex-wrap gap-2">
                            <button class="px-4 py-2 rounded-md bg-primary text-primary-foreground">Default</button>
                            <button class="px-4 py-2 rounded-md bg-primary-700 text-primary-foreground">Hover</button>
                            <button class="px-4 py-2 rounded-md bg-primary-900 text-primary-foreground">Active</button>
                            <button class="px-4 py-2 rounded-md bg-neutral-200 text-neutral-400" disabled>Disabled</button>
                            <button class="px-4 py-2 rounded-md bg-primary text-primary-foreground">Focus me (tab)</button>
                        </div>
                    </div>

                    {{-- accent: ink text, hover=lighter, active=darker --}}
                    <div class="p-5 rounded-lg bg-surface border border-line">
                        <p class="text-xs text-muted mb-3">accent (ink text; hover 200 lighter, active 400 darker)</p>
                        <div class="flex flex-wrap gap-2">
                            <button class="px-4 py-2 rounded-md bg-accent text-accent-foreground">Default</button>
                            <button class="px-4 py-2 rounded-md bg-accent-200 text-accent-foreground">Hover</button>
                            <button class="px-4 py-2 rounded-md bg-accent-400 text-accent-foreground">Active</button>
                            <button class="px-4 py-2 rounded-md bg-neutral-200 text-neutral-400" disabled>Disabled</button>
                        </div>
                    </div>

                    {{-- success: white text, hover/active both darker (700 anchor has no lighter room) --}}
                    <div class="p-5 rounded-lg bg-surface border border-line">
                        <p class="text-xs text-muted mb-3">success (white text; hover 800, active 900 — both darker, no lighter option)</p>
                        <div class="flex flex-wrap gap-2">
                            <button class="px-4 py-2 rounded-md bg-success text-success-foreground">Default</button>
                            <button class="px-4 py-2 rounded-md bg-success-800 text-success-foreground">Hover</button>
                            <button class="px-4 py-2 rounded-md bg-success-900 text-success-foreground">Active</button>
                            <button class="px-4 py-2 rounded-md bg-neutral-200 text-neutral-400" disabled>Disabled</button>
                        </div>
                    </div>

                    {{-- warning: ink text, hover/active both lighter (600 anchor has no darker room) --}}
                    <div class="p-5 rounded-lg bg-surface border border-line">
                        <p class="text-xs text-muted mb-3">warning (ink text; hover 500, active 400 — both lighter, no darker option)</p>
                        <div class="flex flex-wrap gap-2">
                            <button class="px-4 py-2 rounded-md bg-warning text-warning-foreground">Default</button>
                            <button class="px-4 py-2 rounded-md bg-warning-500 text-warning-foreground">Hover</button>
                            <button class="px-4 py-2 rounded-md bg-warning-400 text-warning-foreground">Active</button>
                            <button class="px-4 py-2 rounded-md bg-neutral-200 text-neutral-400" disabled>Disabled</button>
                        </div>
                    </div>

                    {{-- danger: white text, hover/active both darker --}}
                    <div class="p-5 rounded-lg bg-surface border border-line">
                        <p class="text-xs text-muted mb-3">danger (white text; hover 700, active 900 — both darker, no lighter option)</p>
                        <div class="flex flex-wrap gap-2">
                            <button class="px-4 py-2 rounded-md bg-danger text-danger-foreground">Default</button>
                            <button class="px-4 py-2 rounded-md bg-danger-700 text-danger-foreground">Hover</button>
                            <button class="px-4 py-2 rounded-md bg-danger-900 text-danger-foreground">Active</button>
                            <button class="px-4 py-2 rounded-md bg-neutral-200 text-neutral-400" disabled>Disabled</button>
                        </div>
                    </div>

                    {{-- input field with border-interactive --}}
                    <div class="p-5 rounded-lg bg-surface border border-line">
                        <p class="text-xs text-muted mb-3">Input field (border-interactive, 3.07:1 on canvas)</p>
                        <input
                            type="text"
                            placeholder="اسم المنتج / Product name"
                            class="w-full px-3 py-2 rounded-md bg-canvas border border-border-interactive text-ink placeholder:text-muted"
                        >
                    </div>
                </div>
            </section>

            <footer class="text-xs text-muted border-t border-line pt-6">
                Batch 1.2 — this page is removed in Batch 1.6.
            </footer>
        </div>
    </body>
</html>
