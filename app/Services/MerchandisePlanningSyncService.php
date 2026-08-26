<?php

namespace App\Services;

use App\Models\BomItem;
use App\Models\ConsumptionRate;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MerchandisePlanningSyncService
{
    /**
     * Synchronize R&D Consumption Rates into a specific MerchandisePlanning instance.
     */
    public static function syncFromDesign(MerchandisePlanning $planning): int
    {
        // 1. Guard Merchandise Planning Status
        if (in_array($planning->status, ['finalised', 'cancelled'], true)) {
            return 0;
        }

        $project = $planning->project;
        // 2. Guard Project Status & Archive State
        if (! $project || $project->isArchived() || in_array($project->status, ['completed', 'cancelled'], true)) {
            return 0;
        }

        $design = $project->design ?? $planning->design;
        if (! $design) {
            return 0;
        }

        $rates = $design->consumptionRates()->with('material')->get();
        $targetQty = max(1, (int) ($project->target_qty ?? 1));

        return DB::transaction(function () use ($planning, $rates, $targetQty) {
            $activePairs = [];

            foreach ($rates as $rate) {
                $unitPrice = (float) ($rate->material?->price ?? 0);
                $wastageRate = (float) ($rate->wastage_rate ?? config('costing.wastage_pct', 3));
                $wastageMultiplier = 1 + ($wastageRate / 100);
                $plannedQty = (float) $rate->standard_rate * $targetQty * $wastageMultiplier;
                $totalPrice = $plannedQty * $unitPrice;

                $activePairs[] = $rate->material_id.'_'.($rate->component ?? '');

                $existingItem = MerchandisePlanningItem::where('merchandise_planning_id', $planning->id)
                    ->where('material_id', $rate->material_id)
                    ->where(function ($q) use ($rate) {
                        if ($rate->component) {
                            $q->where('component', $rate->component);
                        } else {
                            $q->whereNull('component')->orWhere('component', '');
                        }
                    })
                    ->where(function ($q) {
                        $q->where('is_from_rnd', true)->orWhereNull('is_from_rnd');
                    })
                    ->first();

                if ($existingItem) {
                    $existingItem->update([
                        'component' => $rate->component,
                        'planned_qty' => $plannedQty,
                        'unit' => $rate->unit,
                        'unit_price' => $existingItem->unit_price ?: $unitPrice,
                        'total_price' => $plannedQty * ($existingItem->unit_price ?: $unitPrice),
                        'notes' => $existingItem->notes ?: $rate->notes,
                        'is_from_rnd' => true,
                        'supplier_id' => $existingItem->supplier_id ?? $rate->material?->supplier_id,
                    ]);
                } else {
                    MerchandisePlanningItem::create([
                        'company_id' => $planning->company_id,
                        'merchandise_planning_id' => $planning->id,
                        'material_id' => $rate->material_id,
                        'component' => $rate->component,
                        'supplier_id' => $rate->material?->supplier_id,
                        'planned_qty' => $plannedQty,
                        'unit' => $rate->unit,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'is_subcon' => false,
                        'notes' => $rate->notes,
                        'is_from_rnd' => true,
                    ]);
                }
            }

            // Clean up deleted/orphaned RND items from the planning
            $existingRndItems = MerchandisePlanningItem::where('merchandise_planning_id', $planning->id)
                ->where('is_from_rnd', true)
                ->get();

            foreach ($existingRndItems as $rndItem) {
                $pair = $rndItem->material_id.'_'.($rndItem->component ?? '');
                if (! in_array($pair, $activePairs, true)) {
                    $rndItem->delete();
                }
            }

            $planning->recalculateTotals();

            return $rates->count();
        });
    }

    /**
     * Cascade a Consumption Rate change or deletion to all active unfinalised plannings.
     */
    public static function syncConsumptionRate(ConsumptionRate $rate, bool $isDelete = false): void
    {
        if (! $rate->design_id) {
            return;
        }

        try {
            $projects = Project::where('design_id', $rate->design_id)
                ->whereNull('archived_at')
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->get();

            foreach ($projects as $project) {
                $plannings = MerchandisePlanning::where('project_id', $project->id)
                    ->whereNotIn('status', ['finalised', 'cancelled'])
                    ->get();

                foreach ($plannings as $planning) {
                    self::syncFromDesign($planning);
                }
            }
        } catch (Throwable $e) {
            Log::error("MerchandisePlanningSync error in syncConsumptionRate: {$e->getMessage()}", [
                'design_id' => $rate->design_id,
                'rate_id' => $rate->id,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Re-synchronize plannings when a Project's target_qty or design_id changes.
     */
    public static function syncProject(Project $project): void
    {
        if ($project->isArchived() || in_array($project->status, ['completed', 'cancelled'], true)) {
            return;
        }

        try {
            $plannings = MerchandisePlanning::where('project_id', $project->id)
                ->whereNotIn('status', ['finalised', 'cancelled'])
                ->get();

            foreach ($plannings as $planning) {
                self::syncFromDesign($planning);
            }
        } catch (Throwable $e) {
            Log::error("MerchandisePlanningSync error in syncProject: {$e->getMessage()}", [
                'project_id' => $project->id,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Backward compatibility alias for legacy callers if any.
     */
    public static function syncFromBom(MerchandisePlanning $planning): int
    {
        return self::syncFromDesign($planning);
    }

    /**
     * Backward compatibility methods for BomItem events if any.
     */
    public static function syncBomItem(BomItem $bomItem): void
    {
        if ($bomItem->bom?->design_id) {
            try {
                $projects = Project::where('design_id', $bomItem->bom->design_id)
                    ->whereNull('archived_at')
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->get();

                foreach ($projects as $project) {
                    $plannings = MerchandisePlanning::where('project_id', $project->id)
                        ->whereNotIn('status', ['finalised', 'cancelled'])
                        ->get();

                    foreach ($plannings as $planning) {
                        self::syncFromDesign($planning);
                    }
                }
            } catch (Throwable $e) {
                Log::error("MerchandisePlanningSync error in syncBomItem: {$e->getMessage()}", ['exception' => $e]);
            }
        }
    }

    public static function deleteBomItem(BomItem $bomItem): void
    {
        if ($bomItem->bom?->design_id) {
            try {
                $projects = Project::where('design_id', $bomItem->bom->design_id)
                    ->whereNull('archived_at')
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->get();

                foreach ($projects as $project) {
                    $plannings = MerchandisePlanning::where('project_id', $project->id)
                        ->whereNotIn('status', ['finalised', 'cancelled'])
                        ->get();

                    foreach ($plannings as $planning) {
                        self::syncFromDesign($planning);
                    }
                }
            } catch (Throwable $e) {
                Log::error("MerchandisePlanningSync error in deleteBomItem: {$e->getMessage()}", ['exception' => $e]);
            }
        }
    }
}
