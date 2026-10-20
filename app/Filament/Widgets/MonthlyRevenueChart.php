<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class MonthlyRevenueChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('pios.widgets.monthly_revenue');
    }

    protected function getData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return ['datasets' => [], 'labels' => []];
        }

        $start = CarbonImmutable::now()->startOfMonth()->subMonths(5);

        $labels = [];
        $values = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $start->addMonths($i);

            $labels[] = $month->translatedFormat('M Y');
            $values[] = (float) Order::query()->visibleTo($user)
                ->whereIn('status', [OrderStatus::Paid, OrderStatus::Completed])
                ->whereBetween('ordered_at', [$month->startOfMonth(), $month->endOfMonth()])
                ->sum('total');
        }

        return [
            'datasets' => [[
                'label' => __('pios.widgets.revenue').' (RM)',
                'data' => $values,
                'backgroundColor' => 'rgba(33, 95, 154, 0.15)',
                'borderColor' => '#215F9A',
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.35,
                'pointBackgroundColor' => '#215F9A',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
