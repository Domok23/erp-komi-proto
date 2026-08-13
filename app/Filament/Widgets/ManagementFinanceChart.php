<?php

namespace App\Filament\Widgets;

use App\Models\PoSupplier;
use App\Models\SalesOrder;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ManagementFinanceChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Revenue vs Procurement Cost (Last 6 Months)';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $months = [];
        $salesData = [];
        $procurementData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');

            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $salesSum = SalesOrder::whereBetween('order_date', [$start, $end])->sum('grand_total');
            $procurementSum = PoSupplier::whereBetween('po_date', [$start, $end])->sum('grand_total');

            $salesData[] = $salesSum;
            $procurementData[] = $procurementSum;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sales Revenue',
                    'data' => $salesData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Procurement Cost',
                    'data' => $procurementData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
