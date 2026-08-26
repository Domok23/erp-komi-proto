<?php

namespace App\Livewire\Components;

use App\Models\InventoryStock;
use App\Models\Material;
use App\Services\CompanyContext;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
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

    public function mount(
        ?int $supplierId = null,
        ?int $warehouseId = null,
        bool $onlyInStock = false,
        array $alreadyAddedIds = [],
        string $mode = 'bulk'
    ): void {
        $this->supplierId = $supplierId;
        $this->warehouseId = $warehouseId;
        $this->onlyInStock = $onlyInStock;
        $this->alreadyAddedIds = $alreadyAddedIds;
        $this->mode = $mode;
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

        $payload = [[
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
        ]];

        $this->dispatch('materials-picked', materials: $payload);
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
            ->filtersFormColumns(3)
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
                    ->label('Add Selected Items')
                    ->button()
                    ->color('primary')
                    ->icon('heroicon-m-plus')
                    ->deselectRecordsAfterCompletion()
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
                            ];
                        })->values()->all();

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
