<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ErpStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Active Projects', Project::whereIn('status', ['planning', 'development', 'sampling', 'production'])->count())
                ->description('Projects currently in progress')
                ->descriptionIcon('heroicon-m-folder')
                ->color('primary'),

            Stat::make('Total Sales Orders', '$' . number_format(SalesOrder::where('status', '!=', 'cancelled')->sum('total_amount'), 2))
                ->description('Total active revenue value')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Pending Purchases', PurchaseOrder::whereIn('status', ['draft', 'sent', 'confirmed', 'partial'])->count())
                ->description('POs awaiting confirmation/delivery')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),
        ];
    }
}
