@php
    $dir = request('dir', 'rtl') === 'ltr' ? 'ltr' : 'rtl';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('design-system.title') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-canvas text-ink">
        <div class="container py-8 md:py-12">
            <header class="mb-12 flex flex-col gap-4 border-b border-line pb-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl">{{ __('design-system.title') }}</h1>
                    <p class="mt-1 text-sm text-muted">{{ __('design-system.subtitle') }}</p>
                </div>

                <nav class="flex gap-2 text-sm" aria-label="{{ __('design-system.dir_rtl') }} / {{ __('design-system.dir_ltr') }}">
                    <a href="?dir=rtl" @class(['rounded-md border border-border-interactive px-3 py-1.5', 'bg-primary text-primary-foreground' => $dir === 'rtl'])>{{ __('design-system.dir_rtl') }}</a>
                    <a href="?dir=ltr" @class(['rounded-md border border-border-interactive px-3 py-1.5', 'bg-primary text-primary-foreground' => $dir === 'ltr'])>{{ __('design-system.dir_ltr') }}</a>
                </nav>
            </header>

            {{-- ============================================================
                 Colors
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.colors') }}</h2>

                <p class="mb-2 text-sm text-muted">Neutral</p>
                <div class="mb-8 grid grid-cols-6 gap-2 sm:grid-cols-11">
                    @foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950] as $step)
                        <div class="overflow-hidden rounded-md border border-line">
                            <div class="h-14 bg-neutral-{{ $step }}"></div>
                            <div class="bg-surface py-1 text-center text-xs">{{ $step }}</div>
                        </div>
                    @endforeach
                </div>

                @foreach (['primary' => 800, 'accent' => 300, 'success' => 700, 'warning' => 600, 'danger' => 600] as $color => $anchor)
                    <p class="mb-2 text-sm text-muted">{{ ucfirst($color) }}</p>
                    <div class="mb-6 grid grid-cols-5 gap-2 sm:grid-cols-10">
                        @foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900] as $step)
                            <div class="overflow-hidden rounded-md border border-line">
                                <div class="flex h-12 items-end justify-center bg-{{ $color }}-{{ $step }} pb-1">
                                    @if ($step === $anchor)
                                        <span class="rounded bg-canvas px-1 text-[10px]">{{ $step }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </section>

            {{-- ============================================================
                 Typography
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.typography') }}</h2>
                <div class="space-y-3">
                    @foreach (['xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl'] as $step)
                        <div class="flex items-baseline gap-4">
                            <span class="w-12 shrink-0 text-xs text-muted">{{ $step }}</span>
                            <p class="text-{{ $step }}">{{ __('design-system.title') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ============================================================
                 Form components
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.form') }}</h2>

                <div class="grid gap-8 md:grid-cols-2">
                    <div>
                        <p class="mb-2 text-xs text-muted">{{ __('design-system.states.default') }}</p>
                        <x-form.input label="{{ __('design-system.demo.input_label') }}" placeholder="{{ __('design-system.demo.input_placeholder') }}" hint="{{ __('design-system.demo.input_hint') }}" />
                    </div>
                    <div>
                        <p class="mb-2 text-xs text-muted">{{ __('design-system.states.focus') }} (Tab)</p>
                        <x-form.input label="{{ __('design-system.demo.input_label') }}" placeholder="{{ __('design-system.demo.input_placeholder') }}" autofocus />
                    </div>
                    <div>
                        <p class="mb-2 text-xs text-muted">{{ __('design-system.states.error') }}</p>
                        <x-form.input label="{{ __('design-system.demo.input_label') }}" error="{{ __('design-system.demo.input_error') }}" required />
                    </div>
                    <div>
                        <p class="mb-2 text-xs text-muted">{{ __('design-system.states.disabled') }}</p>
                        <x-form.input label="{{ __('design-system.demo.input_label') }}" value="{{ __('design-system.demo.product_name') }}" disabled />
                    </div>
                </div>

                <div class="mt-8 grid gap-8 md:grid-cols-2">
                    <x-form.textarea label="{{ __('design-system.demo.textarea_label') }}" placeholder="{{ __('design-system.demo.textarea_placeholder') }}" maxlength="200" />

                    <x-form.select
                        label="{{ __('design-system.demo.select_label') }}"
                        placeholder="{{ __('design-system.demo.select_placeholder') }}"
                        :options="[
                            'cairo' => __('design-system.demo.select_option_cairo'),
                            'giza' => __('design-system.demo.select_option_giza'),
                            'alex' => __('design-system.demo.select_option_alex'),
                        ]"
                    />
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-8">
                    <x-form.checkbox label="{{ __('design-system.demo.checkbox_label') }}" />
                    <x-form.checkbox label="{{ __('design-system.states.checked') }}" checked />
                    <x-form.checkbox label="{{ __('design-system.states.indeterminate') }}" indeterminate />
                    <x-form.checkbox label="{{ __('design-system.states.disabled') }}" disabled />
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-8">
                    <x-form.radio name="ds-payment" label="{{ __('design-system.demo.radio_label_card') }}" checked />
                    <x-form.radio name="ds-payment" label="{{ __('design-system.demo.radio_label_wallet') }}" />
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-8">
                    <x-form.toggle label="{{ __('design-system.demo.toggle_label') }}" checked />
                    <x-form.toggle label="{{ __('design-system.states.disabled') }}" disabled />
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-8">
                    <x-form.quantity :value="2" :min="1" :max="10" />
                </div>

                <div class="mt-6 max-w-sm">
                    <x-form.file label="{{ __('design-system.demo.file_label') }}" hint="{{ __('design-system.demo.file_hint') }}" accept="image/png,image/jpeg" />
                </div>
            </section>

            {{-- ============================================================
                 Buttons
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.buttons') }}</h2>

                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.button variant="primary">{{ __('design-system.demo.button_primary') }}</x-ui.button>
                    <x-ui.button variant="secondary">{{ __('design-system.demo.button_secondary') }}</x-ui.button>
                    <x-ui.button variant="outline">{{ __('design-system.demo.button_outline') }}</x-ui.button>
                    <x-ui.button variant="ghost">{{ __('design-system.demo.button_ghost') }}</x-ui.button>
                    <x-ui.button variant="danger">{{ __('design-system.demo.button_danger') }}</x-ui.button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <x-ui.button size="sm">sm</x-ui.button>
                    <x-ui.button size="md">md</x-ui.button>
                    <x-ui.button size="lg">lg</x-ui.button>
                    <x-ui.button loading>{{ __('design-system.demo.button_loading') }}</x-ui.button>
                    <x-ui.button disabled>{{ __('design-system.states.disabled') }}</x-ui.button>
                    <x-ui.button href="#">{{ __('common.view_all') }} (&lt;a&gt;)</x-ui.button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <x-ui.button>
                        <x-slot:iconStart>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M5 12h14" /><path d="M12 5v14" /></svg>
                        </x-slot:iconStart>
                        {{ __('common.add_to_cart') }}
                    </x-ui.button>

                    <div class="w-full max-w-xs">
                        <x-ui.button full-width>{{ __('design-system.demo.button_primary') }}</x-ui.button>
                    </div>
                </div>
            </section>

            {{-- ============================================================
                 Badges
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.badges') }}</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.badge variant="neutral">{{ __('design-system.demo.badge_limited') }}</x-ui.badge>
                    <x-ui.badge variant="success">{{ __('design-system.demo.badge_new') }}</x-ui.badge>
                    <x-ui.badge variant="warning">{{ __('design-system.demo.badge_limited') }}</x-ui.badge>
                    <x-ui.badge variant="danger">{{ __('design-system.demo.badge_out_of_stock') }}</x-ui.badge>
                    <x-ui.badge variant="accent">{{ __('design-system.demo.badge_sale') }}</x-ui.badge>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <x-ui.badge size="sm">sm</x-ui.badge>
                    <x-ui.badge size="md">md</x-ui.badge>
                    <x-ui.badge size="lg">lg</x-ui.badge>
                </div>
            </section>

            {{-- ============================================================
                 Chips
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.chips') }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.chip>{{ __('design-system.demo.chip_size') }}</x-ui.chip>
                    <x-ui.chip>{{ __('design-system.demo.chip_color') }}</x-ui.chip>
                    <x-ui.chip>{{ __('design-system.demo.chip_price') }}</x-ui.chip>
                    <x-ui.chip :removable="false">{{ __('design-system.states.disabled') }}</x-ui.chip>
                </div>
            </section>

            {{-- ============================================================
                 Alerts
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.alerts') }}</h2>
                <div class="space-y-3">
                    <x-ui.alert type="info">{{ __('design-system.demo.alert_info') }}</x-ui.alert>
                    <x-ui.alert type="success" closable>{{ __('design-system.demo.alert_success') }}</x-ui.alert>
                    <x-ui.alert type="warning">{{ __('design-system.demo.alert_warning') }}</x-ui.alert>
                    <x-ui.alert type="danger" closable>{{ __('design-system.demo.alert_danger') }}</x-ui.alert>
                </div>
            </section>

            {{-- ============================================================
                 Loading indicators
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.feedback') }}</h2>

                <div class="flex flex-wrap items-center gap-4">
                    <x-ui.spinner size="sm" />
                    <x-ui.spinner size="md" />
                    <x-ui.spinner size="lg" />
                </div>

                <div class="mt-6 grid gap-6 sm:grid-cols-3">
                    <x-ui.skeleton type="text" :lines="3" />
                    <x-ui.skeleton type="image" class="h-32 w-full" />
                    <x-ui.skeleton type="card" />
                </div>
            </section>

            {{-- ============================================================
                 Tooltip
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.tooltip') }}</h2>
                <x-ui.tooltip id="tooltip-demo" text="{{ __('design-system.demo.tooltip_text') }}">
                    <button type="button" aria-describedby="tooltip-demo" class="rounded-md border border-border-interactive px-3 py-1.5 text-sm">
                        {{ __('design-system.demo.tooltip_trigger') }}
                    </button>
                </x-ui.tooltip>
            </section>

            {{-- ============================================================
                 Cards
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.cards') }}</h2>
                <div class="max-w-sm">
                    <x-ui.card>
                        <x-slot:header>
                            <p class="font-medium text-ink">{{ __('design-system.demo.card_title') }}</p>
                        </x-slot:header>

                        <p class="text-sm text-muted">{{ __('design-system.demo.card_body') }}</p>

                        <x-slot:footer>
                            <x-ui.button variant="outline" size="sm">{{ __('design-system.demo.card_footer_action') }}</x-ui.button>
                        </x-slot:footer>
                    </x-ui.card>
                </div>
            </section>

            {{-- ============================================================
                 Product card
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.product_card') }}</h2>
                <div class="grid max-w-3xl grid-cols-2 gap-6 sm:grid-cols-3">
                    <x-product-card
                        name="{{ __('design-system.demo.product_name') }}"
                        :price="129900"
                        :original-price="179900"
                        :discount-percent="28"
                        :colors="[['token' => 'ink', 'label' => 'Black'], ['token' => 'primary', 'label' => 'Green'], ['token' => 'accent', 'label' => 'Terracotta']]"
                    />
                    <x-product-card
                        name="{{ __('design-system.demo.product_name') }}"
                        :price="89900"
                        :colors="[['token' => 'muted', 'label' => 'Grey']]"
                    />
                </div>
            </section>

            {{-- ============================================================
                 Breadcrumb
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.breadcrumb') }}</h2>
                <x-ui.breadcrumb :items="[
                    ['label' => __('design-system.demo.breadcrumb_home'), 'href' => '#'],
                    ['label' => __('design-system.demo.breadcrumb_category'), 'href' => '#'],
                    ['label' => __('design-system.demo.breadcrumb_product'), 'href' => null],
                ]" />
            </section>

            {{-- ============================================================
                 Pagination
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.pagination') }}</h2>
                <x-ui.pagination :current-page="4" :total-pages="12" base-url="#" />
            </section>

            {{-- ============================================================
                 Empty state
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.empty_state') }}</h2>
                <div class="max-w-sm rounded-xl border border-line">
                    <x-ui.empty-state title="{{ __('design-system.demo.empty_title') }}" description="{{ __('design-system.demo.empty_description') }}">
                        <x-slot:icon>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8"><path d="m21 21-4.34-4.34" /><circle cx="11" cy="11" r="8" /></svg>
                        </x-slot:icon>
                        <x-slot:action>
                            <x-ui.button variant="outline" size="sm">{{ __('design-system.demo.empty_cta') }}</x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                </div>
            </section>

            {{-- ============================================================
                 Rating
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.rating') }}</h2>
                <div class="flex flex-wrap items-center gap-6">
                    <x-ui.rating :value="4.5" />
                    <x-ui.rating :value="3" size="lg" />
                    <x-ui.rating :value="0" :readonly="false" name="ds-rating" />
                </div>
            </section>

            {{-- ============================================================
                 Tabs
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.tabs') }}</h2>
                <x-ui.tabs id="ds-tabs" :tabs="[
                    'overview' => __('design-system.demo.tab_overview'),
                    'details' => __('design-system.demo.tab_details'),
                    'reviews' => __('design-system.demo.tab_reviews'),
                ]">
                    <div id="ds-tabs-panel-overview" role="tabpanel" data-tab-panel="overview" aria-labelledby="ds-tabs-tab-overview" tabindex="0" class="text-sm text-muted">
                        {{ __('design-system.demo.tab_overview_content') }}
                    </div>
                    <div id="ds-tabs-panel-details" role="tabpanel" data-tab-panel="details" aria-labelledby="ds-tabs-tab-details" tabindex="0" class="text-sm text-muted" hidden>
                        {{ __('design-system.demo.tab_details_content') }}
                    </div>
                    <div id="ds-tabs-panel-reviews" role="tabpanel" data-tab-panel="reviews" aria-labelledby="ds-tabs-tab-reviews" tabindex="0" class="text-sm text-muted" hidden>
                        {{ __('design-system.demo.tab_reviews_content') }}
                    </div>
                </x-ui.tabs>
            </section>

            {{-- ============================================================
                 Accordion
            ============================================================ --}}
            <section class="mb-16">
                <h2 class="mb-4 text-xl">{{ __('design-system.sections.accordion') }}</h2>
                <div class="max-w-2xl">
                    <x-ui.accordion :items="[
                        ['title' => __('design-system.demo.accordion_shipping_title'), 'content' => __('design-system.demo.accordion_shipping_content')],
                        ['title' => __('design-system.demo.accordion_returns_title'), 'content' => __('design-system.demo.accordion_returns_content')],
                        ['title' => __('design-system.demo.accordion_payment_title'), 'content' => __('design-system.demo.accordion_payment_content')],
                    ]" />
                </div>
            </section>
        </div>

        @stack('scripts')
    </body>
</html>
