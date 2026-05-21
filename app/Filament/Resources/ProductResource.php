<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
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

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;



    protected static ?string $navigationLabel = 'Products';
    protected static ?string $modelLabel = 'Products';
    protected static ?string $pluralModelLabel = 'Products';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->required(),
            Forms\Components\TextInput::make('code')
                ->required()
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\Select::make('category')
                ->options([
                    'handbag' => 'Handbag',
                    'backpack' => 'Backpack',
                    'duffle' => 'Duffle',
                    'messenger' => 'Messenger',
                    'laptop_bag' => 'Laptop Bag',
                    'trolley' => 'Trolley',
                    'other' => 'Other',
                ]),
            Forms\Components\TextInput::make('unit')
                ->default('pcs')
                ->maxLength(20),
            Forms\Components\TextInput::make('weight_kg')
                ->numeric()
                ->label('Weight (kg)')
                ->step(0.01),
            Forms\Components\Textarea::make('dimensions')
                ->maxLength(100),
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
            Tables\Columns\BadgeColumn::make('category')
                ->colors([
                    'primary' => 'handbag',
                    'warning' => 'backpack',
                    'success' => 'duffle',
                    'info' => 'messenger',
                    'danger' => 'laptop_bag',
                    'gray' => ['trolley', 'other'],
                ]),
            Tables\Columns\TextColumn::make('unit'),
            Tables\Columns\TextColumn::make('weight_kg')->numeric(),
            Tables\Columns\TextColumn::make('dimensions'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('category')->options([
                    'handbag' => 'Handbag',
                    'backpack' => 'Backpack',
                    'duffle' => 'Duffle',
                    'messenger' => 'Messenger',
                    'laptop_bag' => 'Laptop Bag',
                    'trolley' => 'Trolley',
                    'other' => 'Other',
                ]),
                SelectFilter::make('is_active')->options(['1' => 'Active', '0' => 'Inactive']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shopping-bag';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
