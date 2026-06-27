<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchandisePlanningResource\Pages;
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

class MerchandisePlanningResource extends Resource
{
    protected static ?string $model = MerchandisePlanning::class;

    protected static ?string $navigationLabel = 'Merchandise Planning';

    protected static ?string $modelLabel = 'Merchandise Planning';

    protected static ?string $pluralModelLabel = 'Merchandise Plannings';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('project_id')
                ->relationship('project', 'name')
                ->allowHtml()
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} <span class='project-code-prefix'>[{$record->project_code}]</span>")
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (!$state) {
                        $set('design_id', null);
                        $set('items', []);
                        $set('total_material_cost', 0);
                        $set('total_subcon_cost', 0);
                        return;
                    }
                    $project = Project::find($state, ['*']);
                    if ($project) {
                        $set('design_id', $project->design_id);

                        // Auto-fill planning items from Project's BOM if available
                        if ($project->bom) {
                            $items = $project->bom->items->map(function ($bomItem) {
                                $unitPrice = $bomItem->material?->price ?? 0;
                                $plannedQty = $bomItem->quantity_per_unit;

                                return [
                                    'material_id' => $bomItem->material_id,
                                    'planned_qty' => $plannedQty,
                                    'unit' => $bomItem->unit,
                                    'unit_price' => $unitPrice,
                                    'total_price' => $plannedQty * $unitPrice,
                                    'is_subcon' => false,
                                    'notes' => $bomItem->notes,
                                ];
                            })->toArray();

                            $set('items', $items);

                            // Calculate total planning costs
                            $totalMat = array_sum(array_column($items, 'total_price'));
                            $set('total_material_cost', $totalMat);
                            $set('total_subcon_cost', 0);
                        }
                    }
                }),
            Forms\Components\Select::make('design_id')
                ->relationship('design', 'name')
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

            Section::make('Planning Costs')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('total_material_cost')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated(),
                    Forms\Components\TextInput::make('total_subcon_cost')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated(),
                ])->columns(2),

            Forms\Components\Textarea::make('special_instructions')
                ->maxLength(65535)
                ->columnSpanFull(),

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
                                    $set('unit_price', $price);
                                    $set('supplier_id', $material?->supplier_id);

                                    $qty = floatval($get('planned_qty') ?? 1);
                                    $set('total_price', $qty * floatval($price));
                                }),
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
                                ->default(1)
                                ->required()
                                ->minValue(0.01)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = floatval($state);
                                    $price = floatval($get('unit_price'));
                                    $set('total_price', $qty * $price);
                                }),
                            Forms\Components\TextInput::make('unit')
                                ->default('pcs')
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('unit_price')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('total_price')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated(),
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
                                ->maxLength(255),
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->defaultItems(1)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $totalMat = 0;
                            $totalSub = 0;
                            foreach ($state as $item) {
                                $total = floatval($item['total_price'] ?? 0);
                                if (! empty($item['is_subcon'])) {
                                    $totalSub += $total;
                                } else {
                                    $totalMat += $total;
                                }
                            }
                            $set('total_material_cost', $totalMat);
                            $set('total_subcon_cost', $totalSub);
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
            Tables\Columns\TextColumn::make('total_material_cost')->numeric(),
            Tables\Columns\TextColumn::make('total_subcon_cost')->numeric(),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'preliminary' => 'Preliminary',
                    'tech_pack' => 'Tech Pack',
                    'finalised' => 'Finalised',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('project_id')->relationship('project', 'project_code'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    Action::make('generatePO')
                        ->label('Generate POs')
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'finalised')
                        ->action(function ($record) {
                            // Group items by supplier for supplier POs
                            $supplierItems = $record->items->where('is_subcon', false)->groupBy('supplier_id');
                            foreach ($supplierItems as $supplierId => $items) {
                                if (! $supplierId) {
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

                                $subtotal = 0;
                                foreach ($items as $item) {
                                    PoSupplierItem::create([
                                        'po_supplier_id' => $po->id,
                                        'material_id' => $item->material_id,
                                        'description' => $item->notes ?? 'Raw material',
                                        'qty' => $item->planned_qty,
                                        'unit' => $item->unit ?? 'pcs',
                                        'unit_price' => $item->unit_price,
                                        'total_price' => $item->total_price,
                                        'qty_received' => 0,
                                    ]);
                                    $subtotal += $item->total_price;
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

                            Notification::make()
                                ->title('POs generated successfully!')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
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
