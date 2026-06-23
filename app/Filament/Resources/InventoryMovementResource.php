<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

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
            Tables\Columns\TextColumn::make('reference_type'),
            Tables\Columns\TextColumn::make('reference_id'),
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
            ->actions([EditAction::make(), DeleteAction::make()])
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
