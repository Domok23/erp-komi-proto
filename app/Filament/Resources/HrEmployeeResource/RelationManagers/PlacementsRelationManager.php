<?php

namespace App\Filament\Resources\HrEmployeeResource\RelationManagers;

use App\Exceptions\HrPlacementException;
use App\Models\HrEmployeePlacement;
use App\Models\Project;
use App\Services\PlaceEmployeeService;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PlacementsRelationManager extends RelationManager
{
    protected static string $relationship = 'placements';

    protected static ?string $title = 'Project Placements';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('project_id')
                ->relationship('project', 'project_code')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('start_date')
                ->required()
                ->default(now()->toDateString()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.project_code')
                    ->label('Project')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'ended' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('start_date', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord()->status === 'active')
                    ->using(function (array $data, RelationManager $livewire): Model {
                        try {
                            return PlaceEmployeeService::place(
                                $livewire->getOwnerRecord(),
                                Project::findOrFail($data['project_id']),
                                $data['start_date'],
                                Auth::id()
                            );
                        } catch (HrPlacementException $e) {
                            Notification::make()
                                ->title('Placement failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->visible(fn (HrEmployeePlacement $record): bool => $record->status === 'ended'),
            ]);
    }
}
