<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPurchaseOrders extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 2;
    protected static ?string $heading = 'Recent Purchase Orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchaseOrder::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Vendor/Subcon')
                    ->state(fn ($record) => $record->supplier?->name ?? $record->subcon?->name ?? '-'),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Order Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'confirmed' => 'primary',
                        'partial' => 'warning',
                        'received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ]);
    }
}
