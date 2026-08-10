<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Filament\Resources\MaterialResource\RelationManagers;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialUom;
use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static ?string $navigationLabel = 'Material';

    protected static ?string $modelLabel = 'Material';

    protected static ?string $pluralModelLabel = 'Material';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Material Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('size')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('color')
                        ->maxLength(100),
                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->relationship('categoryRef', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('Category Name')->required(),
                            Forms\Components\TextInput::make('code')->label('Code (Optional)'),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            $companyId = CompanyContext::getCompanyId();
                            $cat = MaterialCategory::firstOrCreate(
                                ['company_id' => $companyId, 'name' => trim($data['name'])],
                                ['code' => $data['code'] ?? null]
                            );
                            return $cat->id;
                        })
                        ->createOptionModalHeading('Add New Category')
                        ->required(),
                    Forms\Components\Select::make('uom_id')
                        ->label('UOM')
                        ->relationship('uomRef', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('UOM Name')->validationAttribute('UOM Name')->required(),
                            Forms\Components\TextInput::make('description')->label('Description (Optional)'),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            $companyId = CompanyContext::getCompanyId();
                            $uomModel = MaterialUom::firstOrCreate(
                                ['company_id' => $companyId, 'name' => trim($data['name'])],
                                ['description' => $data['description'] ?? null]
                            );
                            return $uomModel->id;
                        })
                        ->createOptionModalHeading('Add New UOM')
                        ->required(),
                    Forms\Components\TextInput::make('stock')
                        ->numeric()
                        ->step(0.01)
                        ->default(0),
                    Forms\Components\TextInput::make('min_stock')
                        ->numeric()
                        ->step(0.01)
                        ->default(0),
                    Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->step(0.01)
                        ->prefix('Rp')
                        ->default(0),
                    Forms\Components\Toggle::make('is_import')
                        ->label(fn (callable $get) => $get('is_import')
                            ? 'From Import (trans: '.config('costing.import_cost_pct', 5).'%)'
                            : 'From Import'
                        )
                        ->default(false)
                        ->live(),
                    Forms\Components\Select::make('supplier_id')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('categoryRef.name')->label('Category')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('uomRef.name')->label('UOM'),
            Tables\Columns\TextColumn::make('stock')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->sortable(),
            Tables\Columns\TextColumn::make('min_stock')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('price')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('supplier.name')->searchable(),
            Tables\Columns\IconColumn::make('is_import')->boolean()->label('Import Status')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('is_active')
                ->label('Status')
                ->badge()
                ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('categoryRef', 'name'),
                \Filament\Tables\Filters\TernaryFilter::make('is_import')
                    ->label('Import Status')
                    ->placeholder('All')
                    ->trueLabel('Import Only')
                    ->falseLabel('Local Only'),
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
                SelectFilter::make('supplier_id')->relationship('supplier', 'name'),
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

    public static function normalizeDecimal(mixed $value): ?float
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $state = str_replace(' ', '', trim(strval($value)));

        if (preg_match('/^\d{1,3}(\.\d{3})+,\d+$/', $state)) {
            $state = str_replace('.', '', $state);
            $state = str_replace(',', '.', $state);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+\.\d+$/', $state)) {
            $state = str_replace(',', '', $state);
        } elseif (str_contains($state, ',') && ! str_contains($state, '.')) {
            $parts = explode(',', $state);
            if (count($parts) === 2 && strlen($parts[1]) === 3 && (int) $parts[0] > 0) {
                $state = str_replace(',', '', $state);
            } else {
                $state = str_replace(',', '.', $state);
            }
        }

        return is_numeric($state) ? (float) $state : null;
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
