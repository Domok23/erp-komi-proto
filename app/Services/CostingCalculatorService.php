<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Costing;

class CostingCalculatorService
{
    public static function calculateFromBOM(Project $project): array
    {
        $materialCost = 0;
        $bom = $project->bom;

        if ($bom) {
            foreach ($bom->items as $item) {
                $material = $item->material;
                if ($material) {
                    // Qty per unit adjusted for wastage: qty * (1 + wastage_percent / 100)
                    $wastageMultiplier = 1 + ($item->wastage_percent / 100);
                    $qtyAdjusted = $item->quantity_per_unit * $wastageMultiplier;
                    $materialCost += $qtyAdjusted * $material->price;
                }
            }
        }

        return [
            'material_cost' => $materialCost,
        ];
    }

    public static function recalculateCosting(Costing $costing): void
    {
        $materialCost = $costing->material_cost;
        $mpCost = $costing->mp_cost;
        $overheadPct = $costing->overhead_pct;
        $profitPct = $costing->profit_margin_pct;
        $shipping = $costing->shipping_cost;

        $overheadAmount = ($materialCost + $mpCost) * ($overheadPct / 100);
        $landedCost = $materialCost + $mpCost + $overheadAmount + $shipping;
        $profitAmount = $landedCost * ($profitPct / 100);
        $sellingPrice = $landedCost + $profitAmount;

        $costing->update([
            'overhead_amount' => $overheadAmount,
            'landed_cost' => $landedCost,
            'profit_margin_amount' => $profitAmount,
            'selling_price' => $sellingPrice,
        ]);
    }
}
