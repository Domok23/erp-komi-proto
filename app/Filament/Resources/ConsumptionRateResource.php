<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsumptionRateResource\Pages;
use App\Models\ConsumptionRate;
use App\Models\Material;
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

class ConsumptionRateResource extends Resource
{
    protected static ?string $model = ConsumptionRate::class;

    protected static ?string $navigationLabel = 'Consumption Rates';
    protected static ?string $modelLabel = 'Consumption Rate';
    protected static ?string $pluralModelLabel = 'Consumption Rates';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('design_id')
                ->relationship('design', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('material_id')
                ->relationship('material', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $material = Material::find($state);
                    if ($material) {
                        $set('unit', $material->unit);
                    }
                }),
            Forms\Components\TextInput::make('standard_rate')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('unit')
                ->default('pcs')
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('wastage_rate')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('design.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('material.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('standard_rate')->sortable(),
            Tables\Columns\TextColumn::make('unit'),
            Tables\Columns\TextColumn::make('wastage_rate'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('design_id')->relationship('design', 'name'),
                SelectFilter::make('material_id')->relationship('material', 'name'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-presentation-chart-line';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'R&D';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsumptionRates::route('/'),
            'create' => Pages\CreateConsumptionRate::route('/create'),
            'edit' => Pages\EditConsumptionRate::route('/{record}/edit'),
        ];
    }
}
