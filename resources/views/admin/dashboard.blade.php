@extends('layouts.admin')

@section('content')
    <x-admin.page :title="__('admin.dashboard.title')">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-admin.stat-card :label="__('admin.dashboard.stat_orders_today')" :value="$ordersToday" icon="shopping-cart" />
            <x-admin.stat-card :label="__('admin.dashboard.stat_revenue_today')" :value="money($revenueToday)" icon="percent" />
            <x-admin.stat-card :label="__('admin.dashboard.stat_low_stock')" :value="$lowStockCount" icon="alert-triangle" :trend-up="false" />
            <x-admin.stat-card :label="__('admin.dashboard.stat_pending_reviews')" :value="$pendingReviewsCount" icon="inbox" />
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-admin.card class="lg:col-span-2">
                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-ink">{{ __('admin.dashboard.sales_chart_title') }}</h2>
                        <span class="text-xs text-muted">{{ __('admin.dashboard.demo_data_notice') }}</span>
                    </div>
                </x-slot:header>

                <canvas
                    id="admin-sales-chart"
                    data-chart-labels="{{ json_encode($salesChart['labels']) }}"
                    data-chart-values="{{ json_encode($salesChart['data']) }}"
                    height="220"
                ></canvas>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h2 class="text-sm font-semibold text-ink">{{ __('admin.dashboard.low_stock_alerts') }}</h2>
                </x-slot:header>

                @if ($lowStockVariants->isEmpty())
                    <p class="py-6 text-center text-sm text-muted">{{ __('admin.dashboard.no_low_stock') }}</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($lowStockVariants as $variant)
                            <li class="flex items-center justify-between py-2.5 text-sm">
                                <span class="truncate text-ink">{{ $variant->product?->name }}</span>
                                <span class="shrink-0 rounded-full bg-danger/10 px-2 py-0.5 text-xs font-medium text-danger">
                                    {{ $variant->stock_quantity }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>
        </div>

        <x-admin.card class="mt-6">
            <x-slot:header>
                <h2 class="text-sm font-semibold text-ink">{{ __('admin.dashboard.recent_orders') }}</h2>
            </x-slot:header>

            @if ($recentOrders->isEmpty())
                <p class="py-6 text-center text-sm text-muted">{{ __('admin.dashboard.no_recent_orders') }}</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($recentOrders as $order)
                        <li class="flex items-center justify-between py-2.5 text-sm">
                            <span class="font-medium text-ink" dir="ltr">{{ $order->order_number }}</span>
                            <span class="text-muted">{{ money($order->grand_total) }}</span>
                            <span class="text-xs text-muted">{{ $order->placed_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
    </x-admin.page>
@endsection

@push('scripts')
    <script type="module">
        $(function () {
            const canvas = document.getElementById('admin-sales-chart');
            if (!canvas) return;

            import('chart.js/auto').then(({ default: Chart }) => {
                // Canvas 2D can't resolve CSS custom properties itself
                // (var(--color-primary) means nothing to strokeStyle) -
                // the computed value has to be read out as a real color
                // string first.
                const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: JSON.parse(canvas.dataset.chartLabels),
                        datasets: [{
                            data: JSON.parse(canvas.dataset.chartValues),
                            borderColor: primaryColor,
                            tension: 0.35,
                            fill: false,
                        }],
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } },
                    },
                });
            });
        });
    </script>
@endpush
