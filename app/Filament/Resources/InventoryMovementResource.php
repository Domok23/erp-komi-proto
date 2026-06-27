<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Filament\Resources\StockTransfers\StockTransferResource;
use App\Models\GoodsReceipt;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\StockTransfer;
use App\Models\SubconMaterialIn;
use App\Models\SubconMaterialOut;
use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationLabel = 'Inventory Movements';

    protected static ?string $modelLabel = 'Inventory Movement';

    protected static ?string $pluralModelLabel = 'Inventory Movements';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('type')
                ->options([
                    'purchase' => 'Purchase',
                    'production_in' => 'Production In',
                    'production_out' => 'Production Out',
                    'adjustment' => 'Adjustment',
                    'shipment' => 'Shipment',
                    'return_in' => 'Return In',
                    'return_out' => 'Return Out',
                    'transfer_in' => 'Transfer In',
                    'transfer_out' => 'Transfer Out',
                ])
                ->required(),
            Forms\Components\Select::make('inventory_stock_id')
                ->relationship('inventoryStock', 'id')
                ->disabled()
                ->dehydrated()
                ->nullable(),
            Forms\Components\Select::make('material_id')
                ->relationship('material', 'name')
                ->getOptionLabelFromRecordUsing(function ($record) {
                    $companyId = CompanyContext::getCompanyId();
                    $stock = InventoryStock::where('material_id', $record->id)
                        ->where('company_id', $companyId)
                        ->sum('quantity');

                    return "[{$record->code}] {$record->name} (Stock: ".number_format($stock, 2)." {$record->unit})";
                })
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\TextInput::make('before_qty')
                ->numeric()
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('after_qty')
                ->numeric()
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('reference_type')
                ->maxLength(100),
            Forms\Components\TextInput::make('reference_id')
                ->numeric(),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\BadgeColumn::make('type')
                ->color(fn (string $state): string => match ($state) {
                    'purchase' => 'success',
                    'production_in' => 'success',
                    'production_out' => 'danger',
                    'adjustment' => 'warning',
                    'shipment' => 'danger',
                    'return_in' => 'success',
                    'return_out' => 'danger',
                    'transfer_in' => 'info',
                    'transfer_out' => 'info',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('material.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('quantity')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('before_qty')->numeric(),
            Tables\Columns\TextColumn::make('after_qty')->numeric(),
            Tables\Columns\TextColumn::make('reference_type')
                ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                    StockTransfer::class => 'Stock Transfer',
                    GoodsReceipt::class => 'Goods Receipt',
                    SubconMaterialIn::class => 'Subcon Material In',
                    SubconMaterialOut::class => 'Subcon Material Out',
                    default => class_basename($state),
                } : '-')
                ->color('primary')
                ->url(function ($record) {
                    if (! $record->reference_id || ! $record->reference_type) {
                        return null;
                    }

                    return match ($record->reference_type) {
                        StockTransfer::class => StockTransferResource::getUrl('edit', ['record' => $record->reference_id]),
                        GoodsReceipt::class => GoodsReceiptResource::getUrl('edit', ['record' => $record->reference_id]),
                        SubconMaterialOut::class => SubconMaterialOutResource::getUrl('edit', ['record' => $record->reference_id]),
                        SubconMaterialIn::class => SubconMaterialInResource::getUrl('edit', ['record' => $record->reference_id]),
                        default => null,
                    };
                }),
            Tables\Columns\TextColumn::make('reference_id')
                ->label('Reference ID'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])
            ->filters([
                SelectFilter::make('type')->options([
                    'purchase' => 'Purchase',
                    'production_in' => 'Production In',
                    'production_out' => 'Production Out',
                    'adjustment' => 'Adjustment',
                    'shipment' => 'Shipment',
                    'return_in' => 'Return In',
                    'return_out' => 'Return Out',
                    'transfer_in' => 'Transfer In',
                    'transfer_out' => 'Transfer Out',
                ]),
                SelectFilter::make('material_id')->relationship('material', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrows-right-left';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory & Subcon';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMovements::route('/'),
            'create' => Pages\CreateInventoryMovement::route('/create'),
            'edit' => Pages\EditInventoryMovement::route('/{record}/edit'),
        ];
    }
}
