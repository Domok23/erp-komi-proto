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

    protected static ?string $navigationLabel = 'Top Management Dashboard';

    protected static ?int $navigationSort = 5;

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
                    ->with(['supplier', 'project'])
                    ->whereNull('buyer_signature')
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
                \Filament\Actions\Action::make('approveSign')
                    ->label('Approve & Sign')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->modalHeading('Management E-Sign Approval')
                    ->modalDescription('Draw your signature in the box below to authorize this Purchase Order.')
                    ->form([
                        SignaturePad::make('buyer_signature')
                            ->label('Management Signature')
                            ->backgroundColor('rgb(255, 255, 255)')
                            ->penColor('rgb(15, 23, 42)')
                            ->required(),
                    ])
                    ->action(function (PoSupplier $record, array $data) {
                        $record->update([
                            'buyer_signature' => $data['buyer_signature'],
                            'status' => 'ordered', // Mark as ordered once signed
                        ]);

                        Notification::make()
                            ->title('Purchase Order approved & signed successfully!')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->emptyStateHeading('No pending approvals')
            ->emptyStateDescription('All purchase orders have been signed and authorized by top management.');
    }
}
