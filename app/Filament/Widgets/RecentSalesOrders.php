<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentSalesOrders extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Sales Orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SalesOrder::query()->with('customer')->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('so_number')
                    ->label('SO Number')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn (SalesOrder $record): string => SalesOrderResource::getUrl('edit', ['record' => $record]))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('order_date')
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
                        'confirmed' => 'info',
                        'in_production' => 'warning',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25]);
    }
}
