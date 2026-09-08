<?php

namespace App\Filament\Resources;

use App\Filament\Actions\PickMaterialsAction;
use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\MerchandisePlanningResource\Pages;
use App\Filament\Support\MaterialFormFilterHelper;
use App\Models\Component;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\MerchandisePlanning;
use App\Models\Project;
use App\Services\CompanyContext;
use App\Services\MerchandisePlanningSyncService;
use App\Services\PoGenerationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
                        ->relationship(
                            name: 'project',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query, $get, $record) => $query->where(function ($q) use ($get, $record) {
                                $selectedId = $get('project_id') ?? $record?->project_id;
                                $q->whereNull('archived_at');
                                if ($selectedId) {
                                    $q->orWhere('projects.id', $selectedId);
                                }
                            })
                        )
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
                                $set('items', []);
                                $set('total_material_cost', number_format(0, 2, '.', ','));
                                $set('total_subcon_cost', number_format(0, 2, '.', ','));

                                // Auto-fill planning items from Project's R&D Design if available
                                if ($project->design) {
                                    $items = $project->design->consumptionRates->map(function ($rate) use ($project) {
                                        $unitPrice = (float) ($rate->material?->price ?? 0);
                                        $targetQty = max(1, (int) ($project->target_qty ?? 1));
                                        $wastageRate = (float) ($rate->wastage_rate ?? config('costing.wastage_pct', 3));
                                        $wastageMultiplier = 1 + ($wastageRate / 100);
                                        $plannedQty = floatval($rate->standard_rate) * $targetQty * $wastageMultiplier;

                                        return [
                                            'filter_category_id' => $rate->material?->category_id,
                                            'material_id' => $rate->material_id,
                                            'component' => $rate->component,
                                            'supplier_id' => $rate->material?->supplier_id,
                                            'planned_qty' => $plannedQty,
                                            'unit' => $rate->unit ?? $rate->material?->uom ?? 'pcs',
                                            'unit_price' => number_format($unitPrice, 2, '.', ','),
                                            'total_price' => number_format($plannedQty * $unitPrice, 2, '.', ','),
                                            'is_subcon' => false,
                                            'notes' => $rate->notes,
                                            'is_from_rnd' => true,
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
                    Forms\Components\Select::make('sub_project_id')
                        ->label('Sub-Project')
                        ->relationship('subProject', 'name', function ($query, $get) {
                            $projectId = $get('project_id');
                            if ($projectId) {
                                return $query->where('project_id', $projectId);
                            }

                            return $query;
                        })
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->reactive()
                        ->visible(function ($get) {
                            $projectId = $get('project_id');
                            if (! $projectId) {
                                return false;
                            }
                            $project = Project::find($projectId);

                            return $project && $project->hasSubProjects();
                        })
                        ->required(function ($get) {
                            $projectId = $get('project_id');
                            if (! $projectId) {
                                return false;
                            }
                            $project = Project::find($projectId);

                            return $project && $project->hasSubProjects();
                        }),
                    Forms\Components\Select::make('design_id')
                        ->label('R&D Design')
                        ->relationship('design', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.RdDesignResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a>'))
                        ->allowHtml()
                        ->disabled()
                        ->dehydrated()
                        ->nullable(),
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
                ->headerActions([
                    Action::make('resyncFromRnd')
                        ->label('Re-sync from R&D')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn ($record) => $record instanceof MerchandisePlanning && in_array($record->status, ['preliminary', 'tech_pack', 'draft']))
                        ->requiresConfirmation()
                        ->modalHeading('Re-sync Materials from R&D Design')
                        ->modalDescription('This will refresh planned quantities and default prices from the current R&D Consumption Rates while preserving any assigned suppliers, subcons, and custom edits.')
                        ->action(function (MerchandisePlanning $record, $livewire) {
                            $count = MerchandisePlanningSyncService::syncFromDesign($record);
                            Notification::make()
                                ->title('Synchronized from R&D')
                                ->body("{$count} material items refreshed from R&D Consumption Rates.")
                                ->success()
                                ->send();

                            if (method_exists($livewire, 'refreshFormData')) {
                                $livewire->refreshFormData(['items', 'total_material_cost', 'total_subcon_cost']);
                            }
                        }),
                    PickMaterialsAction::make(),
                    StockPreviewAction::make('form'),
                ])
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            MaterialFormFilterHelper::categoryFilter()
                                ->disabled(fn (callable $get) => (bool) $get('is_from_rnd')),
                            MaterialFormFilterHelper::supplierFilter(
                                name: 'supplier_id',
                                label: 'Material Supplier',
                                categoryFieldName: 'filter_category_id',
                                dehydrated: true
                            )
                                ->disabled(fn (callable $get) => (bool) $get('is_from_rnd')),
                            Forms\Components\Select::make('material_id')
                                ->relationship(
                                    'material',
                                    'name',
                                    modifyQueryUsing: fn (Builder $query, callable $get) => MaterialFormFilterHelper::applyFilters(
                                        $query,
                                        $get,
                                        categoryFieldName: 'filter_category_id',
                                        supplierFieldName: 'supplier_id'
                                    )
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.MaterialResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->formatted_select_label.'</a>'))
                                ->allowHtml()
                                ->searchable(['code', 'name', 'color', 'size'])
                                ->preload()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->nullable()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $material = $state ? Material::with(['uomRef', 'categoryRef', 'supplier'])->find($state) : null;
                                    $set('unit', $material?->uom ?? $material?->uomRef?->name ?? $material?->unit);
                                    $price = $material?->price ?? 0;
                                    $set('unit_price', number_format($price, 2, '.', ','));
                                    $set('supplier_id', $material?->supplier_id);
                                    if ($material?->category_id && ! $get('filter_category_id')) {
                                        $set('filter_category_id', $material->category_id);
                                    }

                                    $qty = floatval(str_replace(',', '', $get('planned_qty') ?? '1'));
                                    $set('total_price', number_format($qty * floatval($price), 2, '.', ','));

                                    $companyId = CompanyContext::getCompanyId();
                                    $stock = $state ? (float) InventoryStock::where('material_id', $state)->where('company_id', $companyId)->sum('quantity') : 0;
                                    if (($stock < $qty || $qty <= 0) && $get('is_subcon')) {
                                        $set('is_subcon', false);
                                        $set('subcon_id', null);
                                    }
                                })
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
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
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated(),
                            Forms\Components\TextInput::make('planned_qty')
                                ->required()
                                ->numeric()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = floatval(str_replace(',', '', $state ?? '0'));
                                    $unitPrice = floatval(str_replace(',', '', $get('unit_price') ?? '0'));
                                    $set('total_price', number_format($qty * $unitPrice, 2, '.', ','));

                                    $materialId = $get('material_id');
                                    $companyId = CompanyContext::getCompanyId();
                                    $stock = $materialId ? (float) InventoryStock::where('material_id', $materialId)->where('company_id', $companyId)->sum('quantity') : 0;

                                    if (($qty <= 0 || $stock < $qty) && $get('is_subcon')) {
                                        $set('is_subcon', false);
                                        $set('subcon_id', null);
                                    }
                                })
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated()
                                ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', '') : $state)
                                ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                            Forms\Components\TextInput::make('unit')
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated(),
                            Forms\Components\TextInput::make('unit_price')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $unitPrice = floatval(str_replace(',', '', $state ?? '0'));
                                    $qty = floatval(str_replace(',', '', $get('planned_qty') ?? '0'));
                                    $set('total_price', number_format($qty * $unitPrice, 2, '.', ','));
                                })
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated()
                                ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                                ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                            Forms\Components\TextInput::make('total_price')
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated()
                                ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                                ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                            Forms\Components\TextInput::make('notes')
                                ->maxLength(255)
                                ->disabled(fn (callable $get) => $get('is_from_rnd'))
                                ->dehydrated(),
                            Forms\Components\Toggle::make('is_subcon')
                                ->default(false)
                                ->label('Is Subcon Service')
                                ->inline(false)
                                ->reactive()
                                ->disabled(function (callable $get) {
                                    if ($get('is_from_rnd')) {
                                        return true;
                                    }

                                    $plannedQty = (float) str_replace(',', '', $get('planned_qty') ?? '0');
                                    if ($plannedQty <= 0) {
                                        return true;
                                    }

                                    $materialId = $get('material_id');
                                    if (! $materialId) {
                                        return true;
                                    }

                                    $companyId = CompanyContext::getCompanyId();
                                    $stock = (float) InventoryStock::where('material_id', $materialId)
                                        ->where('company_id', $companyId)
                                        ->sum('quantity');

                                    return $stock < $plannedQty;
                                })
                                ->dehydrated()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        $set('subcon_id', null);
                                    }
                                }),
                            Forms\Components\Select::make('subcon_id')
                                ->relationship('subcon', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->disabled(fn (callable $get) => (bool) $get('is_from_rnd'))
                                ->dehydrated()
                                ->visible(fn (callable $get) => (bool) $get('is_subcon')),
                            Forms\Components\Hidden::make('is_from_rnd')
                                ->default(null),
                        ])
                        ->columns(2)
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
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['project', 'subProject', 'design']))
            ->recordUrl(fn (MerchandisePlanning $record): string => self::getUrl('edit', ['record' => $record]))
            ->recordAction(EditAction::class)
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->searchable()
                    ->html()
                    ->formatStateUsing(function ($state, MerchandisePlanning $record) {
                        if (! $state || ! $record->project_id) {
                            return $state ?? '-';
                        }
                        $url = ProjectResource::getUrl('edit', ['record' => $record->project_id]);
                        $tooltip = $record->project ? "Code: {$record->project->project_code}" : '';

                        return '<a href="'.$url.'" title="'.e($tooltip).'" class="hover:underline text-primary-600 dark:text-primary-400 font-medium cursor-pointer" onclick="event.stopPropagation()">'.e($state).'</a>';
                    })
                    ->tooltip(fn (MerchandisePlanning $record) => $record->project ? "Code: {$record->project->project_code}" : null),
                Tables\Columns\TextColumn::make('subProject.name')
                    ->label('Sub-Project')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('design.name')
                    ->label('R&D Design')
                    ->sortable()
                    ->searchable()
                    ->html()
                    ->formatStateUsing(function ($state, MerchandisePlanning $record) {
                        if (! $state || ! $record->design_id) {
                            return $state ?? '-';
                        }
                        $url = RdDesignResource::getUrl('edit', ['record' => $record->design_id]);
                        $tooltip = $record->design ? "Code: {$record->design->code} (v{$record->design->version})" : '';

                        return '<a href="'.$url.'" title="'.e($tooltip).'" class="hover:underline text-primary-600 dark:text-primary-400 font-medium cursor-pointer" onclick="event.stopPropagation()">'.e($state).'</a>';
                    })
                    ->tooltip(fn (MerchandisePlanning $record) => $record->design ? "Code: {$record->design->code} (v{$record->design->version})" : null),
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
                        ->modalWidth('7xl')
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
                            $service = app(PoGenerationService::class);
                            $result = $service->generateFromPlannings($record);

                            if (empty($result['po_numbers'])) {
                                Notification::make()
                                    ->title('No POs generated')
                                    ->body('All planning items are fully stocked or have no supplier/subcon assigned.')
                                    ->warning()
                                    ->send();
                            } else {
                                $poList = implode(', ', $result['po_numbers']);
                                Notification::make()
                                    ->title('POs generated successfully!')
                                    ->body("Created/Updated: {$poList}")
                                    ->success()
                                    ->send();
                            }
                        }),
                    Action::make('resyncFromDesign')
                        ->label('Re-sync from R&D')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (MerchandisePlanning $record) => in_array($record->status, ['preliminary', 'tech_pack', 'draft']))
                        ->requiresConfirmation()
                        ->modalHeading('Re-sync Materials from R&D Design')
                        ->modalDescription('This will refresh planned quantities and default prices from the current R&D Consumption Rates while preserving any assigned suppliers, subcons, and custom edits.')
                        ->action(function (MerchandisePlanning $record) {
                            $count = MerchandisePlanningSyncService::syncFromDesign($record);
                            Notification::make()
                                ->title('Synchronized from R&D')
                                ->body("{$count} material items refreshed from R&D Consumption Rates.")
                                ->success()
                                ->send();
                        }),
                    StockPreviewAction::make('table'),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkGeneratePO')
                        ->label('Generate Consolidated POs')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('success')
                        ->mountUsing(function (BulkAction $action, Collection $records) {
                            $records->loadMissing('project');
                            $nonFinalised = $records->filter(fn ($r) => $r->status !== 'finalised');

                            if ($nonFinalised->isNotEmpty()) {
                                $invalidList = $nonFinalised->map(function ($r) {
                                    $projectName = $r->project?->name ?? 'Planning #'.$r->id;
                                    $status = ucfirst(str_replace('_', ' ', $r->status));

                                    return "{$projectName} ({$status})";
                                })->implode(', ');

                                Notification::make()
                                    ->title('PO Generation Blocked')
                                    ->body("Cannot generate POs. The following planning(s) are not in 'Finalised' status: {$invalidList}. Please deselect them or finalize them first.")
                                    ->warning()
                                    ->send();

                                $action->halt();
                            }
                        })
                        ->modalHeading('Confirm Consolidated PO Generation')
                        ->modalWidth('7xl')
                        ->modalSubmitActionLabel('Generate Consolidated POs')
                        ->modalContent(function (Collection $records) {
                            $records->load(['items.material', 'items.supplier', 'items.subcon', 'project', 'subProject']);

                            $materialIds = $records->flatMap(fn ($r) => $r->items->pluck('material_id'))->filter()->unique();
                            $companyId = CompanyContext::getCompanyId();
                            $stocks = InventoryStock::whereIn('material_id', $materialIds)
                                ->where('company_id', $companyId)
                                ->select('material_id', DB::raw('SUM(quantity) as total_qty'))
                                ->groupBy('material_id')
                                ->pluck('total_qty', 'material_id')
                                ->toArray();

                            return view('filament.components.generate-po-modal', [
                                'records' => $records,
                                'stocks' => $stocks,
                            ]);
                        })
                        ->action(function (Collection $records, BulkAction $action) {
                            $records->loadMissing('project');
                            $nonFinalised = $records->filter(fn ($r) => $r->status !== 'finalised');

                            if ($nonFinalised->isNotEmpty()) {
                                $invalidList = $nonFinalised->map(function ($r) {
                                    $projectName = $r->project?->name ?? 'Planning #'.$r->id;
                                    $status = ucfirst(str_replace('_', ' ', $r->status));

                                    return "{$projectName} ({$status})";
                                })->implode(', ');

                                Notification::make()
                                    ->title('PO Generation Blocked')
                                    ->body("Cannot generate POs. The following planning(s) are not in 'Finalised' status: {$invalidList}. Please deselect them or finalize them first.")
                                    ->warning()
                                    ->send();

                                $action->halt();

                                return;
                            }

                            $finalisedRecords = $records->where('status', 'finalised');

                            if ($finalisedRecords->isEmpty()) {
                                Notification::make()
                                    ->title('PO Generation Failed')
                                    ->body('None of the selected Merchandise Plannings are in "Finalised" status.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $service = app(PoGenerationService::class);
                            $result = $service->generateFromPlannings($finalisedRecords);

                            if (empty($result['po_numbers'])) {
                                Notification::make()
                                    ->title('No POs generated')
                                    ->body('All required items are fully stocked or have no supplier/subcon assigned.')
                                    ->warning()
                                    ->send();
                            } else {
                                $poList = implode(', ', $result['po_numbers']);
                                Notification::make()
                                    ->title('Consolidated POs Generated Successfully!')
                                    ->body("Processed {$result['processed_count']} planning(s). Created/Updated: {$poList}")
                                    ->success()
                                    ->send();
                            }
                        }),
                    DeleteBulkAction::make(),
                ])
                    ->dropdownWidth(Width::ExtraSmall),
            ]);
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
