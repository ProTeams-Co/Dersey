<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Support\Money;
use Illuminate\View\View;

/**
 * Stat cards and lists below are real counts/sums straight from the
 * database - only the sales chart is placeholder data (explicitly allowed
 * by this batch's scope: "رسم بياني - بيانات وهمية دلوقتي", no reporting
 * engine yet). Nothing here is cached: a dashboard is looked at rarely
 * enough, and needs to be fresh enough, that CLAUDE.md's versioned-cache
 * machinery would be overkill for it.
 */
class DashboardController
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'ordersToday' => $this->ordersToday(),
            'revenueToday' => $this->revenueToday(),
            'lowStockCount' => $this->lowStockCount(),
            'pendingReviewsCount' => $this->pendingReviewsCount(),
            'recentOrders' => $this->recentOrders(),
            'lowStockVariants' => $this->lowStockVariants(),
            'salesChart' => $this->placeholderSalesChart(),
        ]);
    }

    private function ordersToday(): int
    {
        return Order::query()->whereDate('placed_at', today())->count();
    }

    private function revenueToday(): Money
    {
        $minor = (int) Order::query()
            ->whereDate('placed_at', today())
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])
            ->sum('grand_total');

        return Money::fromMinor($minor);
    }

    private function lowStockCount(): int
    {
        return ProductVariant::query()->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
    }

    private function pendingReviewsCount(): int
    {
        return Review::query()->where('status', ReviewStatus::Pending)->count();
    }

    private function recentOrders()
    {
        return Order::query()->latest('placed_at')->limit(5)->get(['id', 'order_number', 'status', 'grand_total', 'placed_at']);
    }

    private function lowStockVariants()
    {
        return ProductVariant::query()
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->with(['product' => fn ($query) => $query->withCurrentTranslation()])
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();
    }

    /**
     * TODO: replace with a real 7-day sales aggregate once an admin
     * reporting service exists - explicitly out of this batch's scope
     * ("مفيش منطق تقارير معقّد - عرض بس").
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    private function placeholderSalesChart(): array
    {
        $labels = collect(range(6, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo)->translatedFormat('D'))->all();
        $data = [12, 19, 8, 15, 22, 17, 25];

        return ['labels' => $labels, 'data' => $data];
    }
}
