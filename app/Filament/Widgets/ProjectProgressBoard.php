<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ProjectMonitor;
use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class ProjectProgressBoard extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1; // Display at the top of the dashboard

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Project Progress Status Board';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::query()
                    ->with(['productionOrders.jobOrders.qcInspections', 'salesOrder.shipments'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('project_code')
                    ->label('Project Code')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Project $record): string => ProjectResource::getUrl('edit', ['record' => $record]))
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Project Name')
                    ->searchable()
                    ->sortable()
                    ->limit(35),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'proto' => 'gray',
                        'sample' => 'info',
                        'mass' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planning' => 'gray',
                        'development' => 'info',
                        'sampling' => 'warning',
                        'approved' => 'primary',
                        'production' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                Tables\Columns\ViewColumn::make('stages')
                    ->label('Production Stage (Job Orders)')
                    ->view('filament.tables.columns.project-stages'),

                Tables\Columns\TextColumn::make('qc_status')
                    ->label('QC Result')
                    ->badge()
                    ->getStateUsing(function (Project $record): string {
                        // Gather all job orders for this project
                        $jobOrders = $record->productionOrders->flatMap->jobOrders;
                        if ($jobOrders->isEmpty()) {
                            return 'no_inspections';
                        }

                        $qcInspections = $jobOrders->flatMap->qcInspections;
                        if ($qcInspections->isEmpty()) {
                            return 'pending';
                        }

                        if ($qcInspections->contains(fn ($inspection) => $inspection->result === 'fail')) {
                            return 'failed';
                        }

                        return 'passed';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'passed' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (string $state, Project $record): string {
                        if ($state === 'passed') {
                            $jobOrders = $record->productionOrders->flatMap->jobOrders;
                            $qcInspections = $jobOrders->flatMap->qcInspections;
                            $passed = $qcInspections->sum('passed_qty');
                            $total = $qcInspections->sum('sample_size');

                            return "Pass ({$passed}/{$total})";
                        }
                        if ($state === 'failed') {
                            $jobOrders = $record->productionOrders->flatMap->jobOrders;
                            $qcInspections = $jobOrders->flatMap->qcInspections;
                            $failed = $qcInspections->sum('failed_qty');

                            return "Fail ({$failed} failed)";
                        }
                        if ($state === 'pending') {
                            return 'Pending QC';
                        }

                        return 'No QC';
                    }),

                Tables\Columns\TextColumn::make('shipping_status')
                    ->label('Shipping Status')
                    ->badge()
                    ->getStateUsing(function (Project $record): string {
                        $shipments = $record->salesOrder?->shipments;
                        if (! $shipments || $shipments->isEmpty()) {
                            return 'not_shipped';
                        }

                        if ($shipments->every(fn ($shp) => $shp->status === 'delivered')) {
                            return 'delivered';
                        }

                        if ($shipments->contains(fn ($shp) => in_array($shp->status, ['in_transit', 'customs']))) {
                            return 'in_transit';
                        }

                        return 'pending';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'in_transit' => 'info',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'delivered' => 'Delivered',
                        'in_transit' => 'In Transit',
                        'pending' => 'Pending Shipment',
                        default => 'Not Shipped',
                    }),

                Tables\Columns\TextColumn::make('target_date')
                    ->label('Deadline')
                    ->date('M d, Y')
                    ->sortable()
                    ->color(function (Project $record): string {
                        if (! $record->target_date) {
                            return 'gray';
                        }

                        if ($record->status !== 'completed' && $record->status !== 'cancelled' && $record->target_date->isPast()) {
                            return 'danger';
                        }

                        if ($record->status !== 'completed' && $record->status !== 'cancelled' && $record->target_date->diffInDays(now()) <= 7) {
                            return 'warning';
                        }

                        return 'gray';
                    })
                    ->description(function (Project $record): ?string {
                        if (! $record->target_date) {
                            return null;
                        }

                        if ($record->status === 'completed') {
                            return 'Project Completed';
                        }

                        if ($record->status === 'cancelled') {
                            return 'Project Cancelled';
                        }

                        $diff = Carbon::now()->startOfDay()->diffInDays($record->target_date->startOfDay(), false);

                        if ($diff < 0) {
                            return 'Overdue by '.abs($diff).' days';
                        }

                        if ($diff === 0) {
                            return 'Due Today!';
                        }

                        return $diff.' days remaining';
                    }),
            ])
            ->headerActions([
                Action::make('open_monitor')
                    ->label('Open Full Project Monitor')
                    ->icon('heroicon-o-chart-bar')
                    ->color('primary')
                    ->url(fn (): string => ProjectMonitor::getUrl()),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
