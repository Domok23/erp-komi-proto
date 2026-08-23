<?php

namespace App\Services;

use App\Models\Costing;
use App\Models\Project;
use App\Models\SubProject;

class CostingCalculatorService
{
    /**
     * Get total MP rate per unit from config.
     */
    public static function getMpRatePerUnit(): int
    {
        return array_sum(config('costing.man_power'));
    }

    /**
     * Calculate MP cost for a given quantity.
     */
    public static function calculateMpCost(int $qty): int
    {
        return self::getMpRatePerUnit() * $qty;
    }

    /**
     * Get default overhead % from config.
     */
    public static function getDefaultOverheadPct(): int
    {
        return (int) config('costing.overhead_pct', 15);
    }

    /**
     * Get default profit margin % from config.
     */
    public static function getDefaultProfitMarginPct(): int
    {
        return (int) config('costing.profit_margin_pct', 20);
    }

    /**
     * Get shipping cost per unit by destination.
     */
    public static function getShippingCostPerUnit(string $destination): int
    {
        return (int) config("costing.shipping.{$destination}", 0);
    }

    /**
     * Calculate material cost from Design / BOM (supports SubProject and R&D Design).
     */
    public static function calculateFromBOM(Project $project, ?SubProject $subProject = null): array
    {
        return self::calculateFromDesignOrBom($project, $subProject);
    }

    public static function calculateFromDesignOrBom(Project $project, ?SubProject $subProject = null): array
    {
        $materialCost = 0;
        $defaultWastage = config('costing.wastage_pct', 3);
        $importCostPct = config('costing.import_cost_pct', 5);

        $bom = $subProject ? $subProject->effectiveBom() : $project->bom;

        if ($bom && $bom->items()->exists()) {
            foreach ($bom->items as $item) {
                $material = $item->material;
                if ($material) {
                    $wastageRate = $item->wastage_percent !== null ? (float) $item->wastage_percent : (float) $defaultWastage;
                    $wastageMultiplier = 1 + ($wastageRate / 100);
                    $qtyAdjusted = (float) $item->quantity_per_unit * $wastageMultiplier;

                    $effectivePrice = (float) $material->price;
                    if ($material->is_import) {
                        $effectivePrice *= (1 + ($importCostPct / 100));
                    }

                    $materialCost += $qtyAdjusted * $effectivePrice;
                }
            }
        } elseif ($project->design) {
            foreach ($project->design->consumptionRates as $rate) {
                $material = $rate->material;
                if ($material) {
                    $wastageRate = $rate->wastage_rate !== null ? (float) $rate->wastage_rate : (float) $defaultWastage;
                    $wastageMultiplier = 1 + ($wastageRate / 100);
                    $qtyAdjusted = (float) $rate->standard_rate * $wastageMultiplier;

                    $effectivePrice = (float) $material->price;
                    if ($material->is_import) {
                        $effectivePrice *= (1 + ($importCostPct / 100));
                    }

                    $materialCost += $qtyAdjusted * $effectivePrice;
                }
            }
        }

        return [
            'material_cost' => $materialCost,
        ];
    }

    /**
     * Recalculate all derived cost fields on a costing.
     */
    public static function recalculateCosting(Costing $costing): void
    {
        if ($costing->isLocked()) {
            return;
        }

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
