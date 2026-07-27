<?php

namespace App\Livewire;

use App\Services\StockPreviewService;
use Filament\Notifications\Notification;
use Livewire\Component;

class StockPreviewModal extends Component
{
    public array $materials = [];

    public float $productionQty = 1.0;

    public array $selected = [];

    public bool $selectAll = false;

    public int $companyId;

    public ?int $projectId = null;

    public array $previews = [];

    public function mount(array $materials, float $productionQty, int $companyId, ?int $projectId = null)
    {
        $this->materials = $materials;
        $this->productionQty = max(0.01, $productionQty);
        $this->companyId = $companyId;
        $this->projectId = $projectId;
        $this->loadPreviews();
    }

    public function updatedProductionQty()
    {
        $this->loadPreviews();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = collect($this->previews)->pluck('materialId')->map(fn ($id) => (string) $id)->all();
        } else {
            $this->selected = [];
        }
    }

    public function loadPreviews()
    {
        $service = app(StockPreviewService::class);
        $previewsCollection = $service->preview(
            materials: $this->materials,
            productionQty: (float) $this->productionQty,
            companyId: $this->companyId,
        );

        $this->previews = $previewsCollection->map(fn ($item) => [
            'materialId' => $item->materialId,
            'materialCode' => $item->materialCode,
            'materialName' => $item->materialName,
            'unit' => $item->unit,
            'quantityPerUnit' => $item->quantityPerUnit,
            'productionQty' => $item->productionQty,
            'required' => $item->required,
            'currentStock' => $item->currentStock,
            'toBuy' => $item->toBuy,
            'status' => $item->status,
            'companyId' => $item->companyId,
        ])->all();
    }

    public function reserveSelected()
    {
        if (empty($this->selected)) {
            return;
        }

        $items = collect($this->selected)
            ->map(fn ($id) => ['material_id' => (int) $id, 'qty' => $this->getQtyFor((int) $id)])
            ->filter(fn ($item) => $item['qty'] > 0)
            ->all();

        if (empty($items)) {
            return;
        }

        $service = app(StockPreviewService::class);
        $service->reserve(reservations: $items, companyId: $this->companyId);

        Notification::make()
            ->title('Stock reserved successfully')
            ->success()
            ->send();

        $this->dispatch('reserved', count($items));
        $this->selected = [];
        $this->selectAll = false;
        $this->loadPreviews();
    }

    public function createPOSelected()
    {
        if (empty($this->selected)) {
            return;
        }

        $items = collect($this->selected)
            ->map(fn ($id) => ['material_id' => (int) $id, 'qty' => $this->getQtyFor((int) $id)])
            ->filter(fn ($item) => $item['qty'] > 0)
            ->all();

        if (empty($items)) {
            return;
        }

        $service = app(StockPreviewService::class);
        $pos = $service->createPurchaseOrder(materials: $items, companyId: $this->companyId, projectId: $this->projectId);

        Notification::make()
            ->title(count($pos).' Purchase Order(s) created as draft')
            ->success()
            ->send();

        $this->dispatch('po-created', count($pos));
        $this->selected = [];
        $this->selectAll = false;
    }

    private function getQtyFor(int $materialId): float
    {
        foreach ($this->previews as $preview) {
            if ($preview['materialId'] === $materialId) {
                return $preview['toBuy'] > 0 ? $preview['toBuy'] : $preview['required'];
            }
        }

        return 0;
    }

    public function render()
    {
        return view('filament.actions.stock-preview-modal');
    }
}
