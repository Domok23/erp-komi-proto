<?php

namespace App\Observers;

use App\Models\ProductionOrder;
use App\Models\Project;

class ProductionOrderObserver
{
    /**
     * Handle the ProductionOrder "saved" event.
     */
    public function saved(ProductionOrder $productionOrder): void
    {
        $this->syncProjectQty($productionOrder);
    }

    /**
     * Handle the ProductionOrder "deleted" event.
     */
    public function deleted(ProductionOrder $productionOrder): void
    {
        $this->syncProjectQty($productionOrder);
    }

    /**
     * Sync completed_qty to parent Project's produced_qty.
     */
    protected function syncProjectQty(ProductionOrder $productionOrder): void
    {
        $project = $productionOrder->project;
        if ($project) {
            $totalProduced = $project->productionOrders()->sum('completed_qty');
            $project->update([
                'produced_qty' => $totalProduced,
            ]);
        }

        $subProject = $productionOrder->subProject;
        if ($subProject) {
            $totalSubProduced = $subProject->productionOrders()->sum('completed_qty');
            $subProject->update([
                'produced_qty' => $totalSubProduced,
            ]);
        }
    }
}
