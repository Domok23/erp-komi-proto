<div>
    @livewire('stock-preview-modal', [
        'materials' => $materials,
        'productionQty' => $productionQty,
        'companyId' => $companyId,
        'projectId' => $projectId,
        'allowReserve' => $allowReserve ?? true,
    ])
</div>
