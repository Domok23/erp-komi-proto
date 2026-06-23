<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Filament\Resources\MaterialResource\RelationManagers;
use App\Models\Material;
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

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;



    protected static ?string $navigationLabel = 'Material';
    protected static ?string $modelLabel = 'Material';
    protected static ?string $pluralModelLabel = 'Material';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('category')
                ->options([
                    'fabric' => 'Fabric',
                    'zipper' => 'Zipper',
                    'button' => 'Button',
                    'thread' => 'Thread',
                    'handle' => 'Handle',
                    'label' => 'Label',
                    'interlining' => 'Interlining',
                    'other' => 'Other',
                ]),
            Forms\Components\TextInput::make('unit')
                ->default('pcs')
                ->maxLength(20),
            Forms\Components\TextInput::make('stock')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('min_stock')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('price')
                ->numeric()
                ->prefix('$')
                ->default(0),
            Forms\Components\Select::make('supplier_id')
                ->relationship('supplier', 'name'),
            Forms\Components\Textarea::make('description')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\BadgeColumn::make('category'),
            Tables\Columns\TextColumn::make('unit'),
            Tables\Columns\TextColumn::make('stock')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('min_stock')->numeric(),
            Tables\Columns\TextColumn::make('price')->numeric()->money('USD')->sortable(),
            Tables\Columns\TextColumn::make('supplier.name')->searchable(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('category')->options([
                    'fabric' => 'Fabric',
                    'zipper' => 'Zipper',
                    'button' => 'Button',
                    'thread' => 'Thread',
                    'handle' => 'Handle',
                    'label' => 'Label',
                    'interlining' => 'Interlining',
                    'other' => 'Other',
                ]),
                SelectFilter::make('is_active')->options(['1' => 'Active', '0' => 'Inactive']),
                SelectFilter::make('supplier_id')->relationship('supplier', 'name'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cube';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InventoryStocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
