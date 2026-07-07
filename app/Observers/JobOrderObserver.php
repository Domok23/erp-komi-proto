<?php

namespace App\Observers;

use App\Models\JobOrder;
use App\Models\ProductionOrder;

class JobOrderObserver
{
    /**
     * Handle the JobOrder "saved" event.
     */
    public function saved(JobOrder $jobOrder): void
    {
        $this->syncProductionOrderQty($jobOrder);
    }

    /**
     * Handle the JobOrder "deleted" event.
     */
    public function deleted(JobOrder $jobOrder): void
    {
        $this->syncProductionOrderQty($jobOrder);
    }

    /**
     * Sync completed_qty to parent ProductionOrder.
     */
    protected function syncProductionOrderQty(JobOrder $jobOrder): void
    {
        $productionOrder = $jobOrder->productionOrder;
        if ($productionOrder) {
            $totalCompleted = $productionOrder->jobOrders()->sum('completed_qty');
            $productionOrder->update([
                'completed_qty' => $totalCompleted,
            ]);
        }
    }
}
