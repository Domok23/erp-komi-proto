<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PoSupplierResource;
use App\Models\PoSupplier;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPurchaseOrders extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 2;

    protected static ?string $heading = 'Recent Supplier Purchase Orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PoSupplier::query()->with('supplier')->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn (PoSupplier $record): string => PoSupplierResource::getUrl('edit', ['record' => $record]))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->state(fn ($record) => $record->supplier?->name ?? '-'),
                Tables\Columns\TextColumn::make('po_date')
                    ->label('Order Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Amount')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'ordered' => 'info',
                        'partial' => 'warning',
                        'received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25]);
    }
}
