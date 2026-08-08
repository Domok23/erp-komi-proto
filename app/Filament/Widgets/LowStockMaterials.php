<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MaterialResource;
use App\Models\Material;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockMaterials extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Low Stock Materials';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Material::query()
                    ->whereColumn('stock', '<=', 'min_stock')
                    ->where('is_active', true)
                    ->orderBy('stock')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Material')
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn (Material $record): string => MaterialResource::getUrl('edit', ['record' => $record]))
                    ->searchable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->suffix(fn ($record) => ' '.$record->unit),
                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Min')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->suffix(fn ($record) => ' '.$record->unit),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25]);
    }
}
