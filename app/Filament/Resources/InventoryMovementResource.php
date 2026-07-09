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
use App\Models\Warehouse;
use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationLabel = 'Inventory Movements';

    protected static ?string $modelLabel = 'Inventory Movement';

    protected static ?string $pluralModelLabel = 'Inventory Movements';

    public static function form(Schema $schema): Schema
    {
        $isDisabled = fn ($record) => $record && $record->reference_type !== null;

        return $schema->schema([
            Section::make('Movement Details')
                ->columnSpanFull()
                ->schema([
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
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateStockDetails($get, $set))
                        ->disabled($isDisabled),
                    Forms\Components\Select::make('direction')
                        ->options([
                            'addition' => 'Addition (+)',
                            'subtraction' => 'Subtraction (-)',
                        ])
                        ->default('addition')
                        ->required(fn (Get $get) => $get('type') === 'adjustment')
                        ->visible(fn (Get $get) => $get('type') === 'adjustment')
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateStockDetails($get, $set))
                        ->disabled($isDisabled),
                    Forms\Components\Select::make('inventory_stock_id')
                        ->label(new HtmlString('Inventory Stock ID <span title="ID relasi ke baris kartu stok fisik (inventory_stocks) barang terkait" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
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
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateStockDetails($get, $set))
                        ->disabled($isDisabled),
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0.01)
                        ->default(0)
                        ->required()
                        ->helperText(fn (Get $get) => $get('type') === 'adjustment'
                            ? 'Adjusts inventory stock up (addition) or down (subtraction) based on Direction.'
                            : null)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateStockDetails($get, $set))
                        ->disabled($isDisabled),
                    Forms\Components\TextInput::make('before_qty')
                        ->label(new HtmlString('Before Qty <span title="Jumlah stok fisik barang di gudang sesaat sebelum transaksi ini diproses" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => is_numeric($state) ? str_replace(',', '', $state) : null),
                    Forms\Components\TextInput::make('after_qty')
                        ->label(new HtmlString('After Qty <span title="Jumlah stok fisik barang di gudang setelah transaksi ini selesai diproses" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => is_numeric($state) ? str_replace(',', '', $state) : null),
                    Forms\Components\TextInput::make('reference_type')
                        ->label(new HtmlString('Reference Type <span title="Nama modul/dokumen asal yang memicu terjadinya pergerakan stok ini" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->maxLength(100)
                        ->hiddenOn('create')
                        ->disabled(),
                    Forms\Components\TextInput::make('reference_id')
                        ->label(new HtmlString('Reference ID <span title="Nomor ID dari dokumen pemicu yang tercatat di Reference Type" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->hiddenOn('create')
                        ->disabled(),
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull()
                        ->disabled($isDisabled),
                ])
                ->columns(2),
        ]);
    }

    public static function updateStockDetails(Get $get, Set $set): void
    {
        $materialId = $get('material_id');
        $type = $get('type');
        $direction = $get('direction') ?? 'addition';
        $quantity = floatval($get('quantity') ?? 0);

        if (! $materialId) {
            $set('inventory_stock_id', null);
            $set('before_qty', 0);
            $set('after_qty', 0);

            return;
        }

        $companyId = CompanyContext::getCompanyId() ?? 1;

        $warehouseId = Warehouse::where('company_id', '=', $companyId, 'and')
            ->where('code', '=', 'WH-MAIN', 'and')
            ->first()?->id ?? Warehouse::where('company_id', '=', $companyId, 'and')->first()?->id;

        if (! $warehouseId) {
            $set('inventory_stock_id', null);
            $set('before_qty', 0);
            $set('after_qty', 0);

            return;
        }

        $stock = InventoryStock::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId)
            ->first();

        $beforeQty = $stock ? floatval($stock->quantity) : 0;
        $set('inventory_stock_id', $stock?->id);
        $set('before_qty', $beforeQty);

        $isSubtraction = false;
        if ($type === 'adjustment') {
            $isSubtraction = $direction === 'subtraction';
        } else {
            $isSubtraction = in_array($type, ['production_out', 'shipment', 'return_out', 'transfer_out']);
        }

        $afterQty = $isSubtraction ? ($beforeQty - $quantity) : ($beforeQty + $quantity);
        $set('after_qty', $afterQty);
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
            Tables\Columns\TextColumn::make('quantity')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->sortable(),
            Tables\Columns\TextColumn::make('before_qty')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('after_qty')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
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
                    DeleteAction::make()
                        ->disabled(fn ($record) => $record && $record->reference_type !== null),
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
