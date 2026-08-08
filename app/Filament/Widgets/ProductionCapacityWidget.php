<?php

namespace App\Filament\Widgets;

use App\Models\ProductionOrder;
use Filament\Widgets\Widget;

class ProductionCapacityWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.production-capacity';

    protected function getViewData(): array
    {
        // Calculate utilization based on planned vs completed quantities in active/planned/running orders
        $activeOrdersQuery = ProductionOrder::whereIn('status', ['planned', 'running', 'in_progress', 'draft']);
        
        $totalPlanned = floatval($activeOrdersQuery->sum('planned_qty'));
        $totalCompleted = floatval($activeOrdersQuery->sum('completed_qty'));
        
        $utilizationRate = $totalPlanned > 0 ? ($totalCompleted / $totalPlanned) * 100 : 0;
        
        $runningOrdersCount = ProductionOrder::whereIn('status', ['running', 'in_progress'])->count();
        $plannedOrdersCount = ProductionOrder::whereIn('status', ['planned', 'draft'])->count();
        $remainingUnits = max(0, $totalPlanned - $totalCompleted);
        $runningOrderRate = $activeOrdersQuery->count() > 0 ? round(($runningOrdersCount / $activeOrdersQuery->count()) * 100) : 0;

        return [
            'totalPlanned' => $totalPlanned,
            'totalCompleted' => $totalCompleted,
            'remainingUnits' => $remainingUnits,
            'utilizationRate' => min(100, round($utilizationRate, 1)),
            'activeOrdersCount' => $activeOrdersQuery->count(),
            'runningOrdersCount' => $runningOrdersCount,
            'plannedOrdersCount' => $plannedOrdersCount,
            'runningOrderRate' => $runningOrderRate,
        ];
    }
}
