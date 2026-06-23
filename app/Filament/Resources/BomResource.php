<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BomResource\Pages;
use App\Models\Bom;
use App\Models\Material;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class BomResource extends Resource
{
    protected static ?string $model = Bom::class;

    protected static ?string $navigationLabel = 'BOM';
    protected static ?string $modelLabel = 'BOM';
    protected static ?string $pluralModelLabel = 'BOMs';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('design_id')
                ->relationship('design', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('version')
                ->default('1.0')
                ->required()
                ->maxLength(20),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'discontinued' => 'Discontinued',
                ])
                ->default('draft')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),

            Section::make('BOM Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')
                                ->relationship('material', 'name')
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    $companyId = \App\Services\CompanyContext::getCompanyId();
                                    $stock = \App\Models\InventoryStock::where('material_id', $record->id)
                                        ->where('company_id', $companyId)
                                        ->sum('quantity');
                                    return "[{$record->code}] {$record->name} (Stock: " . number_format($stock, 2) . " {$record->unit})";
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $material = Material::find($state, ['*']);
                                    if ($material) {
                                        $set('unit', $material->unit);
                                    }
                                }),
                            Forms\Components\Select::make('category')
                                ->options([
                                    'main_material' => 'Main Material',
                                    'hardware' => 'Hardware',
                                    'trim' => 'Trim',
                                    'packaging' => 'Packaging',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('quantity_per_unit')
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('unit')
                                ->default('pcs')
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('wastage_percent')
                                ->numeric()
                                ->default(0),
                            Forms\Components\TextInput::make('notes'),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->columnSpanFull(),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('design.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('version')->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'active' => 'success',
                    'discontinued' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'discontinued' => 'Discontinued',
                ]),
                SelectFilter::make('design_id')->relationship('design', 'name'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-list-bullet';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'R&D & Consumption';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoms::route('/'),
            'create' => Pages\CreateBom::route('/create'),
            'edit' => Pages\EditBom::route('/{record}/edit'),
        ];
    }
}
