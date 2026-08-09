<?php

namespace App\Filament\Resources;

use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\BomResource\Pages;
use App\Forms\Components\NullableToggle;
use App\Models\Bom;
use App\Models\Component;
use App\Models\ConsumptionRate;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Services\CodeGenerator;
use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;

class BomResource extends Resource
{
    protected static ?string $model = Bom::class;

    protected static ?string $navigationLabel = 'BOM';

    protected static ?string $recordTitleAttribute = 'bom_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['bom_number', 'name'];
    }

    protected static ?string $modelLabel = 'BOM';

    protected static ?string $pluralModelLabel = 'BOMs';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('BOM Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('bom_number')
                        ->label('BOM Number')
                        ->placeholder('Generated automatically on selection')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->maxLength(50),
                    Forms\Components\Select::make('design_id')
                        ->relationship('design', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                            if ($state) {
                                $rates = ConsumptionRate::where('design_id', $state)->get();

                                 $items = $rates->map(function ($rate) {
                                    return [
                                        'material_id' => $rate->material_id,
                                        'component' => $rate->component,
                                        'quantity_per_unit' => $rate->standard_rate,
                                        'unit' => $rate->unit,
                                        'wastage_percent' => config('costing.wastage_pct', 3),
                                        'notes' => $rate->notes,
                                        'is_from_rnd' => true,
                                    ];
                                })->toArray();

                                $set('items', $items);
                            } else {
                                $set('items', []);
                            }

                            $version = $get('version') ?: '1.0';
                            $set('bom_number', $state ? CodeGenerator::generateBOMNumber((int) $state, $version) : '');
                        }),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('version')
                        ->default('1.0')
                        ->required()
                        ->maxLength(20)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                            $designId = $get('design_id');
                            $set('bom_number', $designId ? CodeGenerator::generateBOMNumber((int) $designId, $state) : '');
                        })
                        ->unique(
                            table: 'boms',
                            column: 'version',
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('design_id', $get('design_id'))
                        ),
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
                ])
                ->columns(2),

            Section::make('BOM Items')
                ->columnSpanFull()
                ->headerActions([
                    StockPreviewAction::make('form'),
                ])
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')
                                ->relationship('material', 'name')
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    $companyId = CompanyContext::getCompanyId();
                                    $stock = InventoryStock::where('material_id', $record->id)
                                        ->where('company_id', $companyId)
                                        ->sum('quantity');

                                    return "[{$record->code}] {$record->name} (Stock: ".number_format($stock, 2)." {$record->uom})";
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $material = Material::find($state, ['*']);
                                    if ($material) {
                                        $set('unit', $material->uom);
                                    }
                                })
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated(),
                            Forms\Components\TextInput::make('quantity_per_unit')
                                ->numeric()
                                ->step(0.0001)
                                ->required()
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated(),
                            Forms\Components\TextInput::make('unit')
                                ->label('UOM')
                                ->default('pcs')
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('wastage_percent')
                                ->label('Wastage (%)')
                                ->default(config('costing.wastage_pct', 3))
                                ->suffix('%')
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\Select::make('component')
                                ->label('Component')
                                ->options(function ($state) {
                                    $companyId = CompanyContext::getCompanyId();

                                    $options = Component::where('company_id', $companyId)
                                        ->pluck('name', 'name')
                                        ->toArray();

                                    if ($state && ! isset($options[$state])) {
                                        $options[$state] = $state;
                                    }

                                    return $options;
                                })
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Component Name')
                                        ->required(),
                                ])
                                ->createOptionUsing(function (array $data): string {
                                    $companyId = CompanyContext::getCompanyId();
                                    $comp = Component::firstOrCreate([
                                        'company_id' => $companyId,
                                        'name' => trim($data['name']),
                                    ]);

                                    return $comp->name;
                                })
                                ->createOptionModalHeading('Add New Component')
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated()
                                ->nullable(),
                            Forms\Components\TextInput::make('notes')
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated(),
                            NullableToggle::make('is_from_rnd')
                                ->label('from R&D')
                                ->default(null)
                                ->reactive()
                                ->visible(fn (callable $get) => $get('is_from_rnd') !== null),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->columnSpanFull()
                        ->itemLabel(function (array $state): ?HtmlString {
                            return new HtmlString(view('filament.components.rnd-badge', [
                                'visible' => ! empty($state['is_from_rnd']),
                            ])->render());
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('bom_number')->label('BOM Number')->sortable()->searchable(),
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
            ->actions([
                ActionGroup::make([
                    StockPreviewAction::make('table'),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
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
