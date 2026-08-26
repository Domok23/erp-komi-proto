<?php

namespace App\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;

class PickMaterialsAction extends Action
{
    protected string $repeaterName = 'items';

    protected ?Closure $supplierContext = null;

    protected ?Closure $warehouseContext = null;

    protected bool $stockRequired = false;

    protected ?Closure $itemHydrator = null;

    protected bool $showAllocationStep = false;

    protected ?Closure $designIdCallback = null;

    protected ?Closure $alreadyAddedIdsCallback = null;

    public static function getDefaultName(): ?string
    {
        return 'browse_materials';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Browse Material Catalog')
            ->icon('heroicon-m-rectangle-stack')
            ->color('gray')
            ->modalHeading('Browse Material Catalog')
            ->modalWidth('7xl')
            ->stickyModalHeader()
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(function ($record = null, $livewire = null): View {
                $formData = [];
                if ($livewire) {
                    if (method_exists($livewire, 'getFormState')) {
                        $formData = $livewire->getFormState();
                    } elseif (property_exists($livewire, 'data') && is_array($livewire->data)) {
                        $formData = $livewire->data;
                    }
                }

                $get = fn (string $key) => data_get($formData, $key);

                $supplierId = $this->supplierContext ? value($this->supplierContext, $get, $record) : null;
                $warehouseId = $this->warehouseContext ? value($this->warehouseContext, $get, $record) : null;

                if ($this->alreadyAddedIdsCallback) {
                    $alreadyAddedIds = value($this->alreadyAddedIdsCallback, $get, $record) ?? [];
                } else {
                    $existingItems = $formData[$this->repeaterName] ?? [];
                    $alreadyAddedIds = collect($existingItems)->pluck('material_id')->filter()->map(fn ($id) => (int) $id)->all();
                }

                $designId = $this->designIdCallback ? value($this->designIdCallback, $get, $record) : null;

                return view('filament.actions.material-picker-modal-content', [
                    'supplierId' => $supplierId,
                    'warehouseId' => $warehouseId,
                    'onlyInStock' => $this->stockRequired,
                    'alreadyAddedIds' => $alreadyAddedIds,
                    'repeaterName' => $this->repeaterName,
                    'showAllocationStep' => $this->showAllocationStep,
                    'designId' => $designId,
                ]);
            });
    }

    public function showAllocationStep(bool $show = true): static
    {
        $this->showAllocationStep = $show;

        return $this;
    }

    public function designId(Closure|int $id): static
    {
        $this->designIdCallback = $id instanceof Closure ? $id : fn () => $id;

        return $this;
    }

    public function alreadyAddedIds(Closure|array $ids): static
    {
        $this->alreadyAddedIdsCallback = $ids instanceof Closure ? $ids : fn () => $ids;

        return $this;
    }

    public function repeaterName(string $name): static
    {
        $this->repeaterName = $name;

        return $this;
    }

    public function getRepeaterName(): string
    {
        return $this->repeaterName;
    }

    public function supplierContext(Closure $callback): static
    {
        $this->supplierContext = $callback;

        return $this;
    }

    public function warehouseContext(Closure $callback): static
    {
        $this->warehouseContext = $callback;

        return $this;
    }

    public function stockRequired(bool $required = true): static
    {
        $this->stockRequired = $required;

        return $this;
    }

    public function itemHydrator(Closure $callback): static
    {
        $this->itemHydrator = $callback;

        return $this;
    }

    public function hydrateItem(array $material): array
    {
        if ($this->itemHydrator) {
            return ($this->itemHydrator)($material);
        }

        return [
            'material_id' => $material['id'],
            'unit' => $material['uom'] ?? 'pcs',
            'unit_price' => $material['price'] ?? 0,
        ];
    }
}
