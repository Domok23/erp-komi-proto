<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectMaterialReadiness;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Tapp\FilamentProgressBarColumn\Tables\Columns\ProgressBarColumn;

class ProjectMonitor extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \UnitEnum|string|null $navigationGroup = 'Projects';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Project Monitor';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.project-monitor';

    public ?string $lastUpdatedTime = null;

    public function mount(): void
    {
        $this->lastUpdatedTime = now()->format('H:i:s');
    }

    public function refreshPage(): void
    {
        $this->lastUpdatedTime = now()->format('H:i:s');
        $this->dispatch('refreshStats');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::query()
                    ->with(['customer', 'bom.items.material', 'costings', 'salesOrder', 'productionOrders'])
                    ->whereIn('status', ['planning', 'development', 'sampling', 'production'])
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
                    ->limit(30),

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
                        'production' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->default('N/A')
                    ->limit(20),

                ProgressBarColumn::make('produced_qty')
                    ->label('Progress')
                    ->maxValue(fn (Project $record): int => $record->target_qty ?? 0)
                    ->successLabel(fn (Project $record): string => round($record->progressPercent()).'% ('.number_format($record->produced_qty ?? 0).'/'.number_format($record->target_qty ?? 0).')'),

                Tables\Columns\TextColumn::make('target_date')
                    ->label('Target Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->placeholder('Not Set'),

                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('Days Remaining')
                    ->getStateUsing(function (Project $record): string {
                        $days = $record->daysRemaining();
                        if ($days === null) {
                            return 'No Target';
                        }
                        if ($days < 0) {
                            return abs($days).' days overdue';
                        }
                        if ($days === 0) {
                            return 'Due Today!';
                        }

                        return $days.' days left';
                    })
                    ->badge()
                    ->color(function (Project $record): string {
                        $days = $record->daysRemaining();
                        if ($days === null) {
                            return 'gray';
                        }
                        if ($days < 0) {
                            return 'danger';
                        }
                        if ($days <= 7) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('material_readiness')
                    ->label('Materials')
                    ->getStateUsing(function (Project $record): string {
                        $service = new ProjectMaterialReadiness;

                        return $service->getProjectStatus($record);
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ready' => 'success',
                        'Partial' => 'warning',
                        'At Risk' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'planning' => 'Planning',
                        'development' => 'Development',
                        'sampling' => 'Sampling',
                        'production' => 'Production',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->multiple()
                    ->options([
                        'proto' => 'Proto',
                        'sample' => 'Sample',
                        'mass' => 'Mass',
                    ]),

                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->searchable()
                    ->relationship('customer', 'name'),

                Tables\Filters\TernaryFilter::make('overdue')
                    ->label('Overdue Projects Only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('target_date')->where('target_date', '<', Carbon::now()->startOfDay()),
                        false: fn (Builder $query) => $query->where(fn ($q) => $q->whereNull('target_date')->orWhere('target_date', '>=', Carbon::now()->startOfDay())),
                    ),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('View Details')
                    ->hiddenLabel()
                    ->icon('heroicon-m-eye')
                    ->extraAttributes([
                        'title' => '',
                        'x-tooltip' => "{ content: 'View Details', theme: \$store.theme, placement: 'bottom' }",
                    ])
                    ->modalHeading(fn (Project $record): string => "Project Operations — {$record->project_code}")
                    ->modalContent(fn (Project $record) => view('filament.pages.project-monitor-slide-over', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->slideOver()
                    ->modalWidth('4xl'),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
