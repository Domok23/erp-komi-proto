<?php

namespace App\Filament\Pages;

use App\Models\PoSupplier;
use App\Models\SalesOrder;
use App\Models\GoodsReceipt;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class TopManagementDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Executive Dashboard';

    protected static ?string $title = 'Executive Dashboard';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.top-management-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\TopManagementOverview::class,
        ];
    }

    public function getHighValueOrders(): \Illuminate\Support\Collection
    {
        return \App\Models\SalesOrder::with('customer')
            ->orderByDesc('grand_total')
            ->limit(3)
            ->get();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PoSupplier::query()
                    ->with(['supplier', 'project', 'approvals'])
                    ->where('approval_status', 'pending_approval')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('po_date')
                    ->label('PO Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Cost')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->sortable(),
            ])
            ->actions([
                \App\Filament\Resources\PoSupplierResource::getApproveSignAction(),
                \App\Filament\Resources\PoSupplierResource::getRejectApprovalAction(),
            ])
            ->emptyStateHeading('No pending approvals')
            ->emptyStateDescription('All purchase orders have been signed and authorized.');
    }
}
