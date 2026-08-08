<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PoSupplierResource;
use App\Filament\Widgets\TopManagementOverview;
use App\Models\PoSupplier;
use App\Models\SalesOrder;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

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
            TopManagementOverview::class,
        ];
    }

    public function getHighValueOrders(): Collection
    {
        return SalesOrder::with('customer')
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
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn (PoSupplier $record): string => PoSupplierResource::getUrl('edit', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
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
                PoSupplierResource::getApproveSignAction(),
                PoSupplierResource::getRejectApprovalAction(),
            ])
            ->emptyStateHeading('No pending approvals')
            ->emptyStateDescription('All purchase orders have been signed and authorized.')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25]);
    }
}
