<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\PoSupplier;
use App\Models\PoSubcon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ErpStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeProjects = Project::whereIn('status', ['planning', 'development', 'sampling', 'production'])->count();
        
        $salesSum = SalesOrder::where('status', '!=', 'cancelled')->sum('grand_total');
        
        $pendingPOs = PoSupplier::whereIn('status', ['draft', 'ordered', 'partial'])->count() +
                      PoSubcon::whereIn('status', ['draft', 'ordered', 'partial'])->count();

        return [
            Stat::make('Active Projects', $activeProjects)
                ->description('Projects currently in progress')
                ->descriptionIcon('heroicon-m-folder')
                ->color('primary'),

            Stat::make('Total Sales Orders', 'IDR ' . number_format($salesSum, 2))
                ->description('Total active revenue value')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Pending Purchases', $pendingPOs)
                ->description('POs awaiting confirmation/delivery')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),
        ];
    }
}
