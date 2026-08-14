<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\Project;

class MerchandisePlanningSyncService
{
    /**
     * Synchronize all BOM items into a specific MerchandisePlanning instance.
     * Respects ERP rules: protects finalised/cancelled documents and preserves manual items.
     *
     * @return int Number of BOM items synced
     */
    public static function syncFromBom(MerchandisePlanning $planning): int
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

        $bom = $project->bom;
        // 3. Guard BOM Status
        if (! $bom || $bom->status === 'discontinued') {
            return 0;
        }

        $bomItems = $bom->items()->with('material')->get();
        $targetQty = max(1, (int) ($project->target_qty ?? 1));

        $bomPairs = [];

        foreach ($bomItems as $bomItem) {
            $unitPrice = (float) ($bomItem->material?->price ?? 0);
            $wastageMultiplier = 1 + (((float) ($bomItem->wastage_percent ?? 0)) / 100);
            $plannedQty = (float) $bomItem->quantity_per_unit * $targetQty * $wastageMultiplier;
            $totalPrice = $plannedQty * $unitPrice;

            $bomPairs[] = $bomItem->material_id.'_'.($bomItem->component ?? '');

            $existingItem = MerchandisePlanningItem::where('merchandise_planning_id', $planning->id)
                ->where('material_id', $bomItem->material_id)
                ->where(function ($q) use ($bomItem) {
                    if ($bomItem->component) {
                        $q->where('component', $bomItem->component);
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
                    'component' => $bomItem->component,
                    'planned_qty' => $plannedQty,
                    'unit' => $bomItem->unit,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'notes' => $bomItem->notes,
                    'is_from_rnd' => true,
                    'supplier_id' => $existingItem->supplier_id ?? $bomItem->material?->supplier_id,
                ]);
            } else {
                MerchandisePlanningItem::create([
                    'merchandise_planning_id' => $planning->id,
                    'material_id' => $bomItem->material_id,
                    'component' => $bomItem->component,
                    'supplier_id' => $bomItem->material?->supplier_id,
                    'planned_qty' => $plannedQty,
                    'unit' => $bomItem->unit,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'is_subcon' => false,
                    'notes' => $bomItem->notes,
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
            if (! in_array($pair, $bomPairs, true)) {
                $rndItem->delete();
            }
        }

        $planning->recalculateTotals();

        return $bomItems->count();
    }

    /**
     * Cascade a BOM Item creation or update to all active/non-finalised plannings.
     */
    public static function syncBomItem(BomItem $bomItem): void
    {
        if ($bomItem->bom?->status === 'discontinued') {
            return;
        }

        $projects = Project::where('bom_id', $bomItem->bom_id)
            ->whereNull('archived_at')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        foreach ($projects as $project) {
            $plannings = MerchandisePlanning::where('project_id', $project->id)
                ->whereNotIn('status', ['finalised', 'cancelled'])
                ->get();

            foreach ($plannings as $planning) {
                self::syncFromBom($planning);
            }
        }
    }

    /**
     * Cascade a BOM Item deletion to all active/non-finalised plannings.
     */
    public static function deleteBomItem(BomItem $bomItem): void
    {
        if ($bomItem->bom?->status === 'discontinued') {
            return;
        }

        $projects = Project::where('bom_id', $bomItem->bom_id)
            ->whereNull('archived_at')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        foreach ($projects as $project) {
            $plannings = MerchandisePlanning::where('project_id', $project->id)
                ->whereNotIn('status', ['finalised', 'cancelled'])
                ->get();

            foreach ($plannings as $planning) {
                self::syncFromBom($planning);
            }
        }
    }

    /**
     * Re-synchronize plannings when a Project's target_qty or bom_id changes.
     */
    public static function syncProject(Project $project): void
    {
        if ($project->isArchived() || in_array($project->status, ['completed', 'cancelled'], true)) {
            return;
        }

        $plannings = MerchandisePlanning::where('project_id', $project->id)
            ->whereNotIn('status', ['finalised', 'cancelled'])
            ->get();

        foreach ($plannings as $planning) {
            self::syncFromBom($planning);
        }
    }
}
