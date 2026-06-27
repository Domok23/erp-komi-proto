<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryStockResource\Pages;
use App\Models\InventoryStock;
use App\Models\Material;
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

class InventoryStockResource extends Resource
{
    protected static ?string $model = InventoryStock::class;

    protected static ?string $navigationLabel = 'Inventory Stock';

    protected static ?string $modelLabel = 'Inventory Stock';

    protected static ?string $pluralModelLabel = 'Inventory Stocks';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('warehouse_id')
                ->relationship('warehouse', 'name')
                ->searchable()
                ->preload()
                ->required(),
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
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $material = Material::find($state);
                    if ($material) {
                        $set('unit', $material->unit);
                        $set('min_stock', $material->min_stock);
                    }
                }),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\TextInput::make('reserved_qty')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\TextInput::make('available_qty')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\TextInput::make('unit')
                ->disabled()
                ->dehydrated()
                ->default('pcs'),
            Forms\Components\TextInput::make('min_stock')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\TextInput::make('location')
                ->maxLength(100),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('warehouse.name')->sortable(),
            Tables\Columns\TextColumn::make('material.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('quantity')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('min_stock')->numeric(),
            Tables\Columns\BadgeColumn::make('stock_status')
                ->label('Stock Status')
                ->color(fn ($record) => $record->quantity < $record->min_stock ? 'danger' : 'success')
                ->getStateUsing(fn ($record) => $record->quantity < $record->min_stock ? 'Low Stock' : 'Good'),
            Tables\Columns\TextColumn::make('unit'),
            Tables\Columns\TextColumn::make('location'),
        ])
            ->filters([
                SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
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
        return 'heroicon-o-archive-box';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory & Subcon';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryStocks::route('/'),
            'create' => Pages\CreateInventoryStock::route('/create'),
            'edit' => Pages\EditInventoryStock::route('/{record}/edit'),
        ];
    }
}
