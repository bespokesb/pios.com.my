<?php

namespace App\Filament\Widgets;

use App\Enums\CustomerCategory;
use App\Models\Customer;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class CustomersByCategoryChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('pios.widgets.customers_by_category');
    }

    protected function getData(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return ['datasets' => [], 'labels' => []];
        }

        $counts = Customer::query()
            ->visibleTo($user)
            ->selectRaw('category, count(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        $labels = [];
        $data = [];
        $colors = [];

        $palette = [
            CustomerCategory::Lead->value => '#94A3B8',    // slate
            CustomerCategory::Prospect->value => '#38BDF8', // sky
            CustomerCategory::Customer->value => '#215F9A', // brand
        ];

        foreach (CustomerCategory::cases() as $category) {
            $value = (int) ($counts[$category->value] ?? 0);

            if ($value === 0) {
                continue;
            }

            $labels[] = $category->label();
            $data[] = $value;
            $colors[] = $palette[$category->value] ?? '#215F9A';
        }

        return [
            'datasets' => [[
                'label' => __('pios.widgets.customers'),
                'data' => $data,
                'backgroundColor' => $colors,
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
