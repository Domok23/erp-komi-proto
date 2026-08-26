<?php

namespace App\Livewire\Components;

use App\Models\ConsumptionRate;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Services\CompanyContext;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MaterialPickerModal extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public ?int $supplierId = null;

    public ?int $warehouseId = null;

    public bool $onlyInStock = false;

    public array $alreadyAddedIds = [];

    public string $mode = 'bulk';

    public bool $showAllocationStep = false;

    public ?int $designId = null;

    public string $step = 'picker';

    public array $selectedMaterials = [];

    public array $availableComponents = [];

    public function mount(
        ?int $supplierId = null,
        ?int $warehouseId = null,
        bool $onlyInStock = false,
        array $alreadyAddedIds = [],
        string $mode = 'bulk',
        bool $showAllocationStep = false,
        ?int $designId = null
    ): void {
        $this->supplierId = $supplierId;
        $this->warehouseId = $warehouseId;
        $this->onlyInStock = $onlyInStock;
        $this->alreadyAddedIds = $alreadyAddedIds;
        $this->mode = $mode;
        $this->showAllocationStep = $showAllocationStep;
        $this->designId = $designId;
        $this->loadAvailableComponents();
    }

    public function loadAvailableComponents(): void
    {
        $companyId = CompanyContext::getCompanyId();
        $this->availableComponents = \App\Models\Component::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    public function createNewComponent(string $name, ?int $targetIndex = null): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $companyId = CompanyContext::getCompanyId();
        \App\Models\Component::firstOrCreate([
            'company_id' => $companyId,
            'name' => $name,
        ]);

        $this->loadAvailableComponents();

        if ($targetIndex !== null && isset($this->selectedMaterials[$targetIndex])) {
            $this->selectedMaterials[$targetIndex]['component'] = $name;
        }
    }

    public function singleSelect(int $materialId): void
    {
        $material = Material::with(['categoryRef', 'uomRef', 'supplier'])->find($materialId);
        if (! $material) {
            return;
        }

        $companyId = CompanyContext::getCompanyId();
        $stock = (float) InventoryStock::where('material_id', $material->id)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($this->warehouseId, fn ($q) => $q->where('warehouse_id', $this->warehouseId))
            ->sum('available_qty');

        $itemData = [
            'id' => $material->id,
            'code' => $material->code,
            'name' => $material->name,
            'category' => $material->categoryRef?->name ?? $material->category,
            'uom' => $material->uom ?? $material->uomRef?->name ?? 'pcs',
            'price' => (float) ($material->price ?? 0),
            'color' => $material->color,
            'size' => $material->size,
            'supplier_id' => $material->supplier_id,
            'available_stock' => $stock,
            'component' => '',
            'actual_consumption' => null,
            'notes' => '',
        ];

        if ($this->showAllocationStep) {
            $this->selectedMaterials = [$itemData];
            $this->selectedTableRecords = [(string) $material->id];
            $this->loadAvailableComponents();
            $this->step = 'allocation';

            return;
        }

        $this->dispatch('materials-picked', materials: [$itemData]);
    }

    public function backToPicker(): void
    {
        $this->step = 'picker';
        if (! empty($this->selectedMaterials)) {
            $this->selectedTableRecords = array_map('strval', array_column($this->selectedMaterials, 'id'));
        }
    }

    public function removeSelectedMaterial(int $index): void
    {
        unset($this->selectedMaterials[$index]);
        $this->selectedMaterials = array_values($this->selectedMaterials);
        $this->selectedTableRecords = array_map('strval', array_column($this->selectedMaterials, 'id'));

        if (empty($this->selectedMaterials)) {
            $this->step = 'picker';
        }
    }

    public function saveAllocation(): void
    {
        if (empty($this->selectedMaterials) || ! $this->designId) {
            return;
        }

        // Validate that each item has a valid actual_consumption > 0
        foreach ($this->selectedMaterials as $item) {
            $rate = (float) ($item['actual_consumption'] ?? 0);
            if ($rate <= 0) {
                Notification::make()
                    ->title('Actual Consumption Required')
                    ->body('Please enter a valid Actual Consumption (> 0) for '.($item['name'] ?? 'all materials').'.')
                    ->danger()
                    ->send();

                return;
            }
        }

        $companyId = CompanyContext::getCompanyId();

        DB::transaction(function () use ($companyId) {
            foreach ($this->selectedMaterials as $item) {
                $componentName = trim($item['component'] ?? '');
                if ($componentName !== '') {
                    \App\Models\Component::firstOrCreate([
                        'company_id' => $companyId,
                        'name' => $componentName,
                    ]);
                }

                $rate = (float) $item['actual_consumption'];

                ConsumptionRate::updateOrCreate([
                    'company_id' => $companyId,
                    'design_id' => $this->designId,
                    'material_id' => $item['id'],
                ], [
                    'component' => $componentName ?: null,
                    'standard_rate' => $rate,
                    'wastage_rate' => config('costing.wastage_pct', 3),
                    'unit' => $item['uom'] ?: 'pcs',
                    'notes' => trim($item['notes'] ?? '') ?: null,
                ]);
            }
        });

        Notification::make()
            ->title('Consumption Rates Added')
            ->body('Successfully added '.count($this->selectedMaterials).' material(s) to design.')
            ->success()
            ->send();

        $newIds = array_column($this->selectedMaterials, 'id');
        $this->alreadyAddedIds = array_unique(array_merge($this->alreadyAddedIds, $newIds));

        $this->selectedMaterials = [];
        $this->step = 'picker';

        $this->dispatch('close-modal', id: 'browse_materials');
        $this->dispatch('refresh-consumption-rates');
    }

    public function table(Table $table): Table
    {
        $companyId = CompanyContext::getCompanyId();

        return $table
            ->query(
                Material::query()
                    ->with(['categoryRef', 'uomRef', 'supplier'])
                    ->where('is_active', true)
                    ->when($this->supplierId, fn ($q) => $q->where('supplier_id', $this->supplierId))
            )
            ->currentSelectionLivewireProperty('selectedTableRecords')
            ->searchable(['code', 'name', 'color', 'size'])
            ->columns([
                TextColumn::make('name')
                    ->label('Material / Code')
                    ->searchable(['name', 'code'])
                    ->sortable()
                    ->description(function (Material $record) {
                        $isAdded = in_array($record->id, $this->alreadyAddedIds);

                        return $isAdded ? "{$record->code} • (Already in list)" : $record->code;
                    })
                    ->weight('bold'),

                TextColumn::make('categoryRef.name')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->default(fn (Material $record) => $record->category ?? '-'),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->default('-')
                    ->toggleable(),

                TextColumn::make('specs')
                    ->label('Specifications')
                    ->state(function (Material $record) {
                        $lines = array_filter([
                            $record->color ? "Color: {$record->color}" : null,
                            $record->size ? "Size: {$record->size}" : null,
                        ]);

                        return ! empty($lines) ? array_values($lines) : '-';
                    })
                    ->listWithLineBreaks()
                    ->color('gray'),

                TextColumn::make('price')
                    ->label('Unit Price')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) ($state ?? 0), 2, '.', ','))
                    ->sortable(),

                TextColumn::make('available_stock')
                    ->label('Available Stock')
                    ->state(function (Material $record) use ($companyId) {
                        $stock = (float) InventoryStock::where('material_id', $record->id)
                            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                            ->when($this->warehouseId, fn ($q) => $q->where('warehouse_id', $this->warehouseId))
                            ->sum('available_qty');

                        $unit = $record->uom ?? $record->uomRef?->name ?? 'pcs';

                        return number_format($stock, 2).' '.$unit;
                    })
                    ->badge()
                    ->color(function (Material $record) use ($companyId) {
                        $stock = (float) InventoryStock::where('material_id', $record->id)
                            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                            ->when($this->warehouseId, fn ($q) => $q->where('warehouse_id', $this->warehouseId))
                            ->sum('available_qty');

                        return $stock > 0 ? 'success' : 'danger';
                    }),
            ])
            ->filters([
                SelectFilter::make('supplier_id')
                    ->placeholder('All Suppliers')
                    ->relationship('supplier', 'name', modifyQueryUsing: fn ($query) => $companyId ? $query->where('company_id', $companyId) : $query)
                    ->searchable()
                    ->preload()
                    ->hidden(fn () => ! empty($this->supplierId)),

                SelectFilter::make('category_id')
                    ->placeholder('All Categories')
                    ->relationship('categoryRef', 'name', modifyQueryUsing: fn ($query) => $companyId ? $query->where('company_id', $companyId) : $query)
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('in_stock')
                    ->placeholder('All Stock Status')
                    ->trueLabel('In-Stock Only')
                    ->falseLabel('Out-of-Stock Only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('inventoryStocks', function ($q) use ($companyId) {
                            if ($companyId) {
                                $q->where('company_id', $companyId);
                            }
                            if ($this->warehouseId) {
                                $q->where('warehouse_id', $this->warehouseId);
                            }
                            $q->where('available_qty', '>', 0);
                        }),
                        false: fn (Builder $query) => $query->whereDoesntHave('inventoryStocks', function ($q) use ($companyId) {
                            if ($companyId) {
                                $q->where('company_id', $companyId);
                            }
                            if ($this->warehouseId) {
                                $q->where('warehouse_id', $this->warehouseId);
                            }
                            $q->where('available_qty', '>', 0);
                        }),
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 2,
                'md' => 3,
            ])
            ->deferFilters(false)
            ->checkIfRecordIsSelectableUsing(fn (Material $record): bool => ! in_array($record->id, $this->alreadyAddedIds))
            ->actions([
                Action::make('select')
                    ->label(fn (Material $record) => in_array($record->id, $this->alreadyAddedIds) ? 'Added' : 'Select')
                    ->button()
                    ->size('xs')
                    ->color(fn (Material $record) => in_array($record->id, $this->alreadyAddedIds) ? 'gray' : 'primary')
                    ->disabled(fn (Material $record) => in_array($record->id, $this->alreadyAddedIds))
                    ->action(fn (Material $record) => $this->singleSelect($record->id)),
            ])
            ->bulkActions([
                BulkAction::make('add_selected')
                    ->label(fn () => $this->showAllocationStep ? 'Next: Configure Consumption' : 'Add Selected Items')
                    ->button()
                    ->color('primary')
                    ->icon(fn () => $this->showAllocationStep ? 'heroicon-m-arrow-right' : 'heroicon-m-plus')
                    ->deselectRecordsAfterCompletion(fn () => ! $this->showAllocationStep)
                    ->action(function (Collection $records) use ($companyId) {
                        $payload = $records->map(function (Material $material) use ($companyId) {
                            $stock = (float) InventoryStock::where('material_id', $material->id)
                                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                                ->when($this->warehouseId, fn ($q) => $q->where('warehouse_id', $this->warehouseId))
                                ->sum('available_qty');

                            return [
                                'id' => $material->id,
                                'code' => $material->code,
                                'name' => $material->name,
                                'category' => $material->categoryRef?->name ?? $material->category,
                                'uom' => $material->uom ?? $material->uomRef?->name ?? 'pcs',
                                'price' => (float) ($material->price ?? 0),
                                'color' => $material->color,
                                'size' => $material->size,
                                'supplier_id' => $material->supplier_id,
                                'available_stock' => $stock,
                                'component' => '',
                                'actual_consumption' => null,
                                'notes' => '',
                            ];
                        })->values()->all();

                        if ($this->showAllocationStep) {
                            $this->selectedMaterials = $payload;
                            $this->selectedTableRecords = array_map('strval', array_column($payload, 'id'));
                            $this->loadAvailableComponents();
                            $this->step = 'allocation';

                            return;
                        }

                        $this->dispatch('materials-picked', materials: $payload);
                    }),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }

    public function render(): View
    {
        return view('livewire.components.material-picker-modal');
    }
}
