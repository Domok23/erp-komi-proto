<?php

namespace App\Filament\Resources\MaterialResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryStocksRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryStocks';

    protected function modifyRelationshipQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Stock Quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Unit'),
                TextColumn::make('location')
                    ->label('Location/Bin')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
