<div>
    @livewire(\App\Livewire\AssignJobOrderWorkersModal::class, [
        'jobOrderId' => isset($jobOrder) ? $jobOrder->id : null,
        'productionOrderId' => isset($productionOrderId) ? $productionOrderId : (isset($jobOrder) ? $jobOrder->production_order_id : null),
        'assignedWorkers' => $assignedWorkers ?? [],
    ])
</div>
