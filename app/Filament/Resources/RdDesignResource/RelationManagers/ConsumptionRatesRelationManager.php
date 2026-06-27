<?php

namespace App\Filament\Resources\RdDesignResource\RelationManagers;

use App\Models\InventoryStock;
use App\Models\Material;
use App\Services\CompanyContext;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsumptionRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'consumptionRates';

    protected static ?string $title = 'Consumption Rates';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
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

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('material.name')->sortable()->searchable(),
            TextColumn::make('standard_rate')->sortable(),
            TextColumn::make('unit'),
            TextColumn::make('wastage_rate'),
            TextColumn::make('notes')->limit(50),
        ])
            ->filters([])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
