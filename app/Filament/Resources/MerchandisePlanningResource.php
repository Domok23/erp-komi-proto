<?php

namespace App\Filament\Resources;

use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\MerchandisePlanningResource\Pages;
use App\Forms\Components\NullableToggle;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\MerchandisePlanning;
use App\Models\PoSubcon;
use App\Models\PoSubconItem;
use App\Models\PoSupplier;
use App\Models\PoSupplierItem;
use App\Models\Project;
use App\Services\CodeGenerator;
use App\Services\CompanyContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class MerchandisePlanningResource extends Resource
{
    protected static ?string $model = MerchandisePlanning::class;

    protected static ?string $navigationLabel = 'Merchandise Planning';

    protected static ?string $modelLabel = 'Merchandise Planning';

    protected static ?string $pluralModelLabel = 'Merchandise Plannings';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Planning Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.ProjectResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a> <span class="project-code-prefix">['.$record->project_code.']</span>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                $set('design_id', null);
                                $set('items', []);
                                $set('total_material_cost', number_format(0, 2, '.', ','));
                                $set('total_subcon_cost', number_format(0, 2, '.', ','));

                                return;
                            }
                            $project = Project::find($state, ['*']);
                            if ($project) {
                                $set('design_id', $project->design_id);

                                // Auto-fill planning items from Project's BOM if available
                                if ($project->bom) {
                                    $items = $project->bom->items->map(function ($bomItem) use ($project) {
                                        $unitPrice = $bomItem->material?->price ?? 0;
                                        $targetQty = max(1, (int) ($project->target_qty ?? 1));
                                        $wastageMultiplier = 1 + (($bomItem->wastage_percent ?? 0) / 100);
                                        $plannedQty = floatval($bomItem->quantity_per_unit) * $targetQty * $wastageMultiplier;

                                        return [
                                            'material_id' => $bomItem->material_id,
                                            'supplier_id' => $bomItem->material?->supplier_id,
                                            'planned_qty' => $plannedQty,
                                            'unit' => $bomItem->unit,
                                            'unit_price' => number_format($unitPrice, 2, '.', ','),
                                            'total_price' => number_format($plannedQty * $unitPrice, 2, '.', ','),
                                            'is_subcon' => false,
                                            'notes' => $bomItem->notes,
                                            'is_from_rnd' => $bomItem->is_from_rnd ?? true,
                                        ];
                                    })->toArray();

                                    $set('items', $items);

                                    // Calculate total planning costs
                                    $totalMat = array_sum(array_map(fn ($i) => floatval(str_replace(',', '', $i['total_price'])), $items));
                                    $set('total_material_cost', number_format($totalMat, 2, '.', ','));
                                    $set('total_subcon_cost', number_format(0, 2, '.', ','));
                                }
                            }
                        }),
                    Forms\Components\Select::make('design_id')
                        ->relationship('design', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.RdDesignResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a>'))
                        ->allowHtml()
                        ->disabled()
                        ->dehydrated()
                        ->required(),
                    Forms\Components\DatePicker::make('planning_date')
                        ->default(now()->toDateString())
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'preliminary' => 'Preliminary',
                            'tech_pack' => 'Tech Pack',
                            'finalised' => 'Finalised',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('preliminary')
                        ->required(),
                    Forms\Components\Textarea::make('special_instructions')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Planning Costs')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('total_material_cost')
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('total_subcon_cost')
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                ])->columns(2),

            Section::make('Materials & Services Planning')
                ->columnSpanFull()
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

                                    return "[{$record->code}] {$record->name} (Stock: ".number_format($stock, 2)." {$record->unit})";
                                })
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $material = $state ? Material::find($state, ['*']) : null;
                                    $set('unit', $material?->unit);
                                    $price = $material?->price ?? 0;
                                    $set('unit_price', number_format($price, 2, '.', ','));
                                    $set('supplier_id', $material?->supplier_id);

                                    $qty = floatval($get('planned_qty') ?? 1);
                                    $set('total_price', number_format($qty * floatval($price), 2, '.', ','));
                                })
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated(),
                            Forms\Components\Select::make('supplier_id')
                                ->relationship('supplier', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\Select::make('subcon_id')
                                ->relationship('subcon', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->disabled(fn (callable $get) => ! $get('is_subcon'))
                                ->dehydrated(),
                            Forms\Components\TextInput::make('planned_qty')
                                ->numeric()
                                ->step(0.01)
                                ->default(1)
                                ->required()
                                ->minValue(0.01)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = floatval($state);
                                    $price = floatval(str_replace(',', '', $get('unit_price')));
                                    $set('total_price', number_format($qty * $price, 2, '.', ','));
                                })
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated(),
                            Forms\Components\TextInput::make('unit')
                                ->default('pcs')
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('unit_price')
                                ->default(0)
                                ->required()
                                ->disabled()
                                ->dehydrated()
                                ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                                ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                            Forms\Components\TextInput::make('total_price')
                                ->default(0)
                                ->disabled()
                                ->dehydrated()
                                ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                                ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                            Forms\Components\Toggle::make('is_subcon')
                                ->default(false)
                                ->label('Is Subcon Service')
                                ->inline(false)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        $set('subcon_id', null);
                                    }
                                }),
                            Forms\Components\TextInput::make('notes')
                                ->maxLength(255)
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated(),
                            NullableToggle::make('is_from_rnd')
                                ->label('from R&D')
                                ->default(null)
                                ->reactive()
                                ->visible(fn (callable $get) => $get('is_from_rnd') !== null),
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->defaultItems(1)
                        ->reactive()
                        ->itemLabel(function (array $state): ?HtmlString {
                            return new HtmlString(view('filament.components.rnd-badge', [
                                'visible' => ! empty($state['is_from_rnd']),
                            ])->render());
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            $totalMat = 0;
                            $totalSub = 0;
                            foreach ($state as $item) {
                                $total = floatval(str_replace(',', '', $item['total_price'] ?? 0));
                                if (! empty($item['is_subcon'])) {
                                    $totalSub += $total;
                                } else {
                                    $totalMat += $total;
                                }
                            }
                            $set('total_material_cost', number_format($totalMat, 2, '.', ','));
                            $set('total_subcon_cost', number_format($totalSub, 2, '.', ','));
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('project.name')
                ->label('Project')
                ->sortable()
                ->searchable()
                ->extraAttributes(fn ($record) => [
                    'title' => $record->project?->project_code,
                ]),
            Tables\Columns\TextColumn::make('design.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('planning_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'preliminary' => 'gray',
                    'tech_pack' => 'info',
                    'finalised' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('total_material_cost')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('total_subcon_cost')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'preliminary' => 'Preliminary',
                    'tech_pack' => 'Tech Pack',
                    'finalised' => 'Finalised',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('project_id')->relationship('project', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('generatePO')
                        ->label('Generate POs')
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'finalised')
                        ->modalHeading('Confirm PO Generation')
                        ->modalSubmitActionLabel('Generate POs')
                        ->modalContent(function ($record) {
                            $record->load(['items.material', 'items.supplier', 'items.subcon']);

                            $materialIds = $record->items->pluck('material_id')->filter()->unique();
                            $companyId = CompanyContext::getCompanyId();
                            $stocks = InventoryStock::whereIn('material_id', $materialIds)
                                ->where('company_id', $companyId)
                                ->select('material_id', DB::raw('SUM(quantity) as total_qty'))
                                ->groupBy('material_id')
                                ->pluck('total_qty', 'material_id')
                                ->toArray();

                            return view('filament.components.generate-po-modal', [
                                'record' => $record,
                                'stocks' => $stocks,
                            ]);
                        })
                        ->action(function ($record) {
                            $generatedPoNumbers = [];

                            // Group items by supplier for supplier POs
                            $supplierItems = $record->items->where('is_subcon', false)->groupBy('supplier_id');
                            foreach ($supplierItems as $supplierId => $items) {
                                if (! $supplierId) {
                                    continue;
                                }

                                $poItemsData = [];
                                $subtotal = 0;

                                foreach ($items as $item) {
                                    $stock = InventoryStock::where('material_id', $item->material_id)
                                        ->where('company_id', $record->company_id)
                                        ->sum('quantity');
                                    $shortage = max(0, floatval($item->planned_qty) - floatval($stock));

                                    if ($shortage <= 0) {
                                        continue;
                                    }

                                    $itemTotalPrice = $shortage * floatval($item->unit_price);
                                    $poItemsData[] = [
                                        'material_id' => $item->material_id,
                                        'description' => $item->notes ?? 'Raw material',
                                        'qty' => $shortage,
                                        'unit' => $item->unit ?? 'pcs',
                                        'unit_price' => $item->unit_price,
                                        'total_price' => $itemTotalPrice,
                                        'qty_received' => 0,
                                    ];
                                    $subtotal += $itemTotalPrice;
                                }

                                if (empty($poItemsData)) {
                                    continue;
                                }

                                $po = PoSupplier::create([
                                    'company_id' => $record->company_id,
                                    'po_number' => CodeGenerator::generatePOSupplierNo(),
                                    'project_id' => $record->project_id,
                                    'supplier_id' => $supplierId,
                                    'po_date' => now()->toDateString(),
                                    'status' => 'draft',
                                ]);

                                $generatedPoNumbers[] = $po->po_number;

                                foreach ($poItemsData as $itemData) {
                                    $itemData['po_supplier_id'] = $po->id;
                                    PoSupplierItem::create($itemData);
                                }

                                $ppn = $subtotal * 0.11; // 11% PPN
                                $po->update([
                                    'subtotal' => $subtotal,
                                    'ppn_percent' => 11,
                                    'ppn_amount' => $ppn,
                                    'grand_total' => $subtotal + $ppn,
                                ]);
                            }

                            // Group items by subcon for subcon POs
                            $subconItems = $record->items->where('is_subcon', true)->groupBy('subcon_id');
                            foreach ($subconItems as $subconId => $items) {
                                if (! $subconId) {
                                    continue;
                                }

                                $po = PoSubcon::create([
                                    'company_id' => $record->company_id,
                                    'po_number' => CodeGenerator::generatePOSubconNo(),
                                    'project_id' => $record->project_id,
                                    'subcon_id' => $subconId,
                                    'po_date' => now()->toDateString(),
                                    'status' => 'draft',
                                ]);

                                $generatedPoNumbers[] = $po->po_number;

                                $serviceCost = 0;
                                foreach ($items as $item) {
                                    PoSubconItem::create([
                                        'po_subcon_id' => $po->id,
                                        'description' => $item->notes ?? 'Subcon service',
                                        'qty' => $item->planned_qty,
                                        'unit_price' => $item->unit_price,
                                        'total_price' => $item->total_price,
                                    ]);
                                    $serviceCost += $item->total_price;
                                }

                                $po->update([
                                    'service_cost' => $serviceCost,
                                    'total_cost' => $serviceCost,
                                ]);
                            }

                            if (empty($generatedPoNumbers)) {
                                Notification::make()
                                    ->title('No POs generated')
                                    ->body('All planning items are fully stocked or have no supplier/subcon assigned.')
                                    ->warning()
                                    ->send();
                            } else {
                                $poList = implode(', ', $generatedPoNumbers);
                                Notification::make()
                                    ->title('POs generated successfully!')
                                    ->body("Created: {$poList}")
                                    ->success()
                                    ->send();
                            }
                        }),
                    StockPreviewAction::make('table'),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shopping-bag';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Merchandising';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMerchandisePlannings::route('/'),
            'create' => Pages\CreateMerchandisePlanning::route('/create'),
            'edit' => Pages\EditMerchandisePlanning::route('/{record}/edit'),
        ];
    }
}
