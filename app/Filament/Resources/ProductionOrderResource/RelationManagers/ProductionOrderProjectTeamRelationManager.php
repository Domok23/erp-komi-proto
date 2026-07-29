<?php

namespace App\Filament\Resources\ProductionOrderResource\RelationManagers;

use App\Filament\Resources\HrEmployeeResource;
use App\Models\HrEmployeePlacement;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ProductionOrderProjectTeamRelationManager extends RelationManager
{
    protected static string $relationship = 'projectPlacements';

    protected static ?string $title = 'Project Team Members (Collaborators)';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.employee_number')
                    ->label('Employee No')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee Name')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state, HrEmployeePlacement $record) => new HtmlString(
                        '<a href="'.HrEmployeeResource::getUrl('edit', ['record' => $record->employee_id]).'" class="ref-link">'.$state.'</a>'
                    )),
                Tables\Columns\TextColumn::make('employee.department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('employee.position.name')
                    ->label('Position')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('Present'),
                Tables\Columns\BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'ended' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'ended' => 'Ended',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                ViewAction::make(),
            ]);
    }
}
