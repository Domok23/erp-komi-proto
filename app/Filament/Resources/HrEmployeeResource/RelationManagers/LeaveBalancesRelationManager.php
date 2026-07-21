<?php

namespace App\Filament\Resources\HrEmployeeResource\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveBalancesRelationManager extends RelationManager
{
    protected static string $relationship = 'leaveBalances';

    protected static ?string $title = 'Leave Balances';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('leave_type_id')
                ->relationship('leaveType', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('year')
                ->numeric()
                ->default(now()->year)
                ->required(),
            Forms\Components\TextInput::make('quota_days')
                ->numeric()
                ->minValue(0)
                ->required(),
            Forms\Components\TextInput::make('used_days')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quota_days')
                    ->sortable(),
                Tables\Columns\TextColumn::make('used_days')
                    ->sortable(),
                Tables\Columns\TextColumn::make('id')
                    ->label('Remaining')
                    ->state(fn ($record) => $record->remainingDays()),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
