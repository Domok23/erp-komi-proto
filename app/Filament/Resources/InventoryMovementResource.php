<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
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

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;



    protected static ?string $navigationLabel = 'Inventory Movements';
    protected static ?string $modelLabel = 'Inventory Movements';
    protected static ?string $pluralModelLabel = 'Inventory Movements';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->required(),
            Forms\Components\Select::make('movement_type')
                ->options([
                    'in' => 'In (Masuk)',
                    'out' => 'Out (Keluar)',
                    'transfer' => 'Transfer',
                    'adjustment' => 'Adjustment',
                    'usage' => 'Usage',
                ])
                ->required(),
            Forms\Components\Select::make('material_id')
                ->relationship('material', 'name')
                ->nullable(),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('reference_type')
                ->maxLength(100),
            Forms\Components\TextInput::make('reference_id')
                ->numeric(),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('created_by')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\BadgeColumn::make('movement_type')
                ->color(fn (string $state): string => match ($state) {
                    'in' => 'success',
                    'out' => 'danger',
                    'transfer' => 'info',
                    'adjustment' => 'warning',
                    'usage' => 'gray',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('material.code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('material.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('quantity')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('reference_type'),
            Tables\Columns\TextColumn::make('reference_id'),
            Tables\Columns\TextColumn::make('created_by'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('movement_type')->options(['in' => 'In', 'out' => 'Out', 'transfer' => 'Transfer', 'adjustment' => 'Adjustment', 'usage' => 'Usage']),
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
        return 'Inventory';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMovements::route('/'),
            'create' => Pages\CreateInventoryMovement::route('/create'),
            'edit' => Pages\EditInventoryMovement::route('/{record}/edit'),
        ];
    }
}
