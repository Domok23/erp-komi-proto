<?php

namespace App\Filament\Widgets;

use App\Models\GoodsReceipt;
use App\Models\PoSupplier;
use App\Models\Project;
use App\Models\SalesOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TopManagementOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $totalRevenue = SalesOrder::sum('grand_total');
        $totalProcurement = PoSupplier::sum('grand_total');

        $pendingPoApprovals = PoSupplier::where('approval_status', 'pending_approval')->count();
        $pendingSoApprovals = SalesOrder::whereNull('customer_signature')->count();
        $pendingGrApprovals = GoodsReceipt::whereNull('receiver_signature')->count();
        $totalPendingApprovals = $pendingPoApprovals + $pendingSoApprovals + $pendingGrApprovals;

        $activeProjects = Project::whereIn('status', ['planning', 'development', 'sampling', 'production'])->count();

        return [
            Stat::make('Total Sales Revenue', 'IDR '.number_format($totalRevenue, 2))
                ->description('Accumulated Sales Order total')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Procurement Cost', 'IDR '.number_format($totalProcurement, 2))
                ->description('Accumulated Purchase Order total')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Pending E-Sign Approvals', $totalPendingApprovals)
                ->description("POs: {$pendingPoApprovals} | SOs: {$pendingSoApprovals} | GRNs: {$pendingGrApprovals}")
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color($totalPendingApprovals > 0 ? 'warning' : 'success'),
            Stat::make('Active Projects', $activeProjects)
                ->description('Projects currently in progress')
                ->descriptionIcon('heroicon-m-cog')
                ->color('info'),
        ];
    }
}
