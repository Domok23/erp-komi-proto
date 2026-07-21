<?php

namespace App\Filament\Resources\HrEmployeeResource\RelationManagers;

use App\Models\HrDepartment;
use App\Models\HrPosition;
use App\Services\PromoteEmployeeService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PositionHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'positionHistories';

    protected static ?string $title = 'Position History';

    public function table(Table $table): Table
    {
        $companyId = $this->getOwnerRecord()->company_id;

        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->color(fn (string $state): string => match ($state) {
                        'promotion' => 'success',
                        'demotion' => 'danger',
                        'transfer' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('fromPosition.name')
                    ->label('From Position')
                    ->sortable(),
                Tables\Columns\TextColumn::make('toPosition.name')
                    ->label('To Position')
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(50),
            ])
            ->defaultSort('effective_date', 'desc')
            ->headerActions([
                Action::make('promote')
                    ->label('Promosi/Demosi')
                    ->form([
                        Forms\Components\Select::make('to_department_id')
                            ->label('Department')
                            ->options(fn (): array => HrDepartment::query()
                                ->where('company_id', $companyId)
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('to_position_id')
                            ->label('Position')
                            ->options(fn (): array => HrPosition::query()
                                ->where('company_id', $companyId)
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'promotion' => 'Promosi',
                                'demotion' => 'Demosi',
                                'transfer' => 'Transfer',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('effective_date')
                            ->required(),
                        Forms\Components\Textarea::make('reason'),
                    ])
                    ->action(function (array $data) {
                        PromoteEmployeeService::apply($this->getOwnerRecord(), $data, Auth::id());

                        Notification::make()
                            ->title('Position updated')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
