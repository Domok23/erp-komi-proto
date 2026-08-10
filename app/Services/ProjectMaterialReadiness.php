<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\Project;

class ProjectMaterialReadiness
{
    /**
     * Get the overall readiness status badge for a project.
     * Options: 'Ready', 'Partial', 'At Risk', 'N/A'
     */
    public function getProjectStatus(Project $project): string
    {
        $details = $this->getDetails($project);

        return $details['overall_status'];
    }

    /**
     * Get detailed breakdown of materials required and available for a project.
     */
    public function getDetails(Project $project): array
    {
        $bom = $project->bom;

        if (! $bom || ! $project->target_qty || $project->target_qty <= 0) {
            return [
                'overall_status' => 'N/A',
                'total_items' => 0,
                'ready_items' => 0,
                'items' => [],
            ];
        }

        $items = $bom->items()->with(['material.supplier'])->get();

        if ($items->isEmpty()) {
            return [
                'overall_status' => 'N/A',
                'total_items' => 0,
                'ready_items' => 0,
                'items' => [],
            ];
        }

        $resultItems = [];
        $readyCount = 0;
        $atRiskCount = 0;

        foreach ($items as $item) {
            $wastage = config('costing.wastage_pct', 3);
            $qtyNeeded = (float) $item->quantity_per_unit * (1 + ($wastage / 100)) * (float) $project->target_qty;

            // Fetch available stock for this material in the project's company
            $qtyAvailable = (float) InventoryStock::where('material_id', $item->material_id)
                ->where('company_id', $project->company_id)
                ->sum('available_qty');

            if ($qtyAvailable == 0 && $item->material) {
                // Fallback to total_stock on material if InventoryStock record is not explicitly created
                $qtyAvailable = (float) ($item->material->total_stock ?? 0);
            }

            if ($qtyAvailable >= ($qtyNeeded * 1.1)) {
                $status = 'Ready';
                $readyCount++;
            } elseif ($qtyAvailable >= $qtyNeeded) {
                $status = 'Ready';
                $readyCount++;
            } elseif ($qtyAvailable > 0) {
                $status = 'Partial';
            } else {
                $status = 'At Risk';
                $atRiskCount++;
            }

            $resultItems[] = [
                'material_name' => $item->material?->name ?? 'Unknown Material',
                'material_code' => $item->material?->material_code ?? $item->material?->code ?? '-',
                'category' => $item->category ?? 'raw',
                'qty_needed' => round($qtyNeeded, 2),
                'qty_available' => round($qtyAvailable, 2),
                // Note: bom_items table retains 'unit' column for UOM snapshots
                'unit' => $item->unit ?? $item->material?->uom ?? 'pcs',
                'status' => $status,
                'supplier_name' => $item->material?->supplier?->name ?? 'N/A',
            ];
        }

        $totalItems = count($resultItems);

        if ($readyCount === $totalItems) {
            $overallStatus = 'Ready';
        } elseif ($atRiskCount === $totalItems) {
            $overallStatus = 'At Risk';
        } else {
            $overallStatus = 'Partial';
        }

        return [
            'overall_status' => $overallStatus,
            'total_items' => $totalItems,
            'ready_items' => $readyCount,
            'items' => $resultItems,
        ];
    }
}
