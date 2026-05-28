<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Models\Inventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;



    protected static ?string $navigationLabel = 'Inventory';
    protected static ?string $modelLabel = 'Inventory';
    protected static ?string $pluralModelLabel = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
                        Forms\Components\Select::make('warehouse_type')
                ->options([
                    'main' => 'Main Warehouse',
                    'branch' => 'Branch',
                    'subcon' => 'Subcontractor',
                ]),
            Forms\Components\Select::make('material_id')
                ->relationship('material', 'name')
                ->nullable(),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('reserved_qty')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('available_qty')
                ->numeric()
                ->default(0),
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
            Tables\Columns\BadgeColumn::make('warehouse_type')
                ->colors(['primary' => 'main', 'warning' => 'branch', 'info' => 'subcon']),
            Tables\Columns\TextColumn::make('material.code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('material.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('quantity')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('reserved_qty')->numeric(),
            Tables\Columns\TextColumn::make('available_qty')->numeric(),
            Tables\Columns\TextColumn::make('location'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('warehouse_type')->options(['main' => 'Main Warehouse', 'branch' => 'Branch', 'subcon' => 'Subcontractor']),
                SelectFilter::make('material_id')->relationship('material', 'name'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-archive-box';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
