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
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(function (callable $get): View {
                $supplierId = $this->supplierContext ? value($this->supplierContext, $get) : null;
                $warehouseId = $this->warehouseContext ? value($this->warehouseContext, $get) : null;
                $existingItems = $get($this->repeaterName) ?? [];
                $alreadyAddedIds = collect($existingItems)->pluck('material_id')->filter()->map(fn ($id) => (int) $id)->all();

                return view('filament.actions.material-picker-modal-content', [
                    'supplierId' => $supplierId,
                    'warehouseId' => $warehouseId,
                    'onlyInStock' => $this->stockRequired,
                    'alreadyAddedIds' => $alreadyAddedIds,
                    'repeaterName' => $this->repeaterName,
                ]);
            });
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
