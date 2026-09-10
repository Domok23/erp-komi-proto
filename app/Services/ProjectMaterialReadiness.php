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
        if (! $project->target_qty || $project->target_qty <= 0) {
            return [
                'overall_status' => 'N/A',
                'total_items' => 0,
                'ready_items' => 0,
                'items' => [],
            ];
        }

        $design = $project->design;
        $items = collect();
        $sourceType = null;

        if ($design && $design->consumptionRates()->exists()) {
            $sourceType = 'design';
            $items = $design->relationLoaded('consumptionRates')
                ? $design->consumptionRates
                : $design->consumptionRates()->with(['material.supplier', 'material.categoryRef'])->get();
        } elseif ($project->bom) {
            $sourceType = 'bom';
            $items = $project->bom->relationLoaded('items')
                ? $project->bom->items
                : $project->bom->items()->with(['material.supplier'])->get();
        }

        if ($items->isEmpty()) {
            return [
                'overall_status' => 'N/A',
                'total_items' => 0,
                'ready_items' => 0,
                'items' => [],
            ];
        }

        $materialIds = $items->pluck('material_id')->filter()->unique()->all();
        $stockMap = ! empty($materialIds)
            ? InventoryStock::whereIn('material_id', $materialIds)
                ->where('company_id', $project->company_id)
                ->groupBy('material_id')
                ->selectRaw('material_id, SUM(available_qty) as total_available')
                ->pluck('total_available', 'material_id')
                ->toArray()
            : [];

        $resultItems = [];
        $readyCount = 0;
        $atRiskCount = 0;

        foreach ($items as $item) {
            $defaultWastage = config('costing.wastage_pct', 3);

            if ($sourceType === 'design') {
                $material = $item->material;
                $wastage = $item->wastage_rate !== null ? (float) $item->wastage_rate : (float) $defaultWastage;
                $qtyNeeded = (float) $item->standard_rate * (1 + ($wastage / 100)) * (float) $project->target_qty;
                $category = $material?->categoryRef?->name ?? $material?->category ?? 'raw';
                $unit = $item->unit ?? $material?->uom ?? 'pcs';
            } else {
                $material = $item->material;
                $wastage = $item->wastage_percent !== null ? (float) $item->wastage_percent : (float) $defaultWastage;
                $qtyNeeded = (float) $item->quantity_per_unit * (1 + ($wastage / 100)) * (float) $project->target_qty;
                $category = $item->category ?? 'raw';
                $unit = $item->unit ?? $material?->uom ?? 'pcs';
            }

            // Fetch available stock for this material in the project's company
            $qtyAvailable = (float) ($stockMap[$item->material_id] ?? 0);

            if ($qtyAvailable == 0 && $material) {
                // Fallback to total_stock on material if InventoryStock record is not explicitly created
                $qtyAvailable = (float) ($material->total_stock ?? $material->stock ?? 0);
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
                'material_name' => $material?->name ?? 'Unknown Material',
                'material_code' => $material?->material_code ?? $material?->code ?? '-',
                'category' => $category,
                'qty_needed' => round($qtyNeeded, 2),
                'qty_available' => round($qtyAvailable, 2),
                'unit' => $unit,
                'status' => $status,
                'supplier_name' => $material?->supplier?->name ?? 'N/A',
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
