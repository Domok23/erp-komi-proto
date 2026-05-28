<?php

namespace App\Filament\Widgets;

use App\Models\SalesOrder;
use Filament\Widgets\ChartWidget;

class SalesTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected ?string $heading = 'Sales Revenue Trend';
    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');

            $data[] = (float) SalesOrder::query()
                ->whereYear('order_date', $date->year)
                ->whereMonth('order_date', $date->month)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Monthly Sales ($)',
                    'data' => $data,
                    'borderColor' => '#fbbf24',
                    'backgroundColor' => 'rgba(251, 191, 36, 0.1)',
                    'fill' => 'start',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
