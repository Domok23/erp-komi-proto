<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\MerchandisePlanning;
use App\Models\PoSubcon;
use App\Models\PoSubconItem;
use App\Models\PoSupplier;
use App\Models\PoSupplierItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PoGenerationService
{
    /**
     * Generate consolidated Supplier and Subcon POs from one or multiple Merchandise Plannings.
     *
     * @param  Collection<int, MerchandisePlanning>|array<int, MerchandisePlanning>|MerchandisePlanning  $plannings
     * @return array{
     *     po_numbers: array<string>,
     *     supplier_pos: array<PoSupplier>,
     *     subcon_pos: array<PoSubcon>,
     *     processed_count: int,
     *     skipped_items_count: int
     * }
     */
    public function generateFromPlannings(Collection|iterable|MerchandisePlanning $plannings): array
    {
        if ($plannings instanceof MerchandisePlanning) {
            $plannings = new Collection([$plannings]);
        } elseif (! ($plannings instanceof Collection)) {
            $plannings = new Collection($plannings);
        }

        return DB::transaction(function () use ($plannings) {
            $generatedPoNumbers = [];
            $createdSupplierPos = [];
            $createdSubconPos = [];
            $skippedItemsCount = 0;

            if ($plannings->isEmpty()) {
                return [
                    'po_numbers' => [],
                    'supplier_pos' => [],
                    'subcon_pos' => [],
                    'processed_count' => 0,
                    'skipped_items_count' => 0,
                ];
            }

            // Eager load relations
            $plannings->loadMissing(['items.material', 'items.supplier', 'items.subcon', 'project']);

            // Get active company ID
            $companyId = CompanyContext::getCompanyId() ?? $plannings->first()->company_id;

            // 1. Gather all material IDs to get stock snapshot
            $materialIds = $plannings->flatMap(function ($planning) {
                return $planning->items->where('is_subcon', false)->pluck('material_id');
            })->filter()->unique()->values()->all();

            // Build running stock map: material_id => total available stock
            $runningStock = [];
            if (! empty($materialIds)) {
                $runningStock = InventoryStock::whereIn('material_id', $materialIds)
                    ->where('company_id', $companyId)
                    ->select('material_id', DB::raw('SUM(quantity) as total_qty'))
                    ->groupBy('material_id')
                    ->pluck('total_qty', 'material_id')
                    ->map(fn ($val) => (float) $val)
                    ->toArray();
            }

            // Data buckets grouped by supplier_id and subcon_id
            $pendingSupplierItems = []; // supplier_id => [ 'project_ids' => [...], 'items' => [...] ]
            $pendingSubconItems = [];   // subcon_id => [ 'project_ids' => [...], 'items' => [...] ]

            // Process each planning sequentially (FIFO for stock allocation)
            foreach ($plannings as $planning) {
                foreach ($planning->items as $item) {
                    if ($item->is_subcon) {
                        // Subcon Service item
                        if (! $item->subcon_id) {
                            $skippedItemsCount++;

                            continue;
                        }

                        $subconId = $item->subcon_id;
                        if (! isset($pendingSubconItems[$subconId])) {
                            $pendingSubconItems[$subconId] = [
                                'project_ids' => [],
                                'items' => [],
                            ];
                        }

                        if ($planning->project_id) {
                            $pendingSubconItems[$subconId]['project_ids'][] = $planning->project_id;
                        }

                        $pendingSubconItems[$subconId]['items'][] = [
                            'project_id' => $planning->project_id,
                            'sub_project_id' => $planning->sub_project_id,
                            'component' => $item->component,
                            'description' => $item->notes ?? 'Subcon service',
                            'qty' => $item->planned_qty,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                        ];
                    } else {
                        // Raw Material item
                        if (! $item->supplier_id || ! $item->material_id) {
                            $skippedItemsCount++;

                            continue;
                        }

                        $matId = $item->material_id;
                        $plannedQty = (float) $item->planned_qty;
                        $currentStock = $runningStock[$matId] ?? 0.0;

                        // Deduct stock FIFO
                        if ($currentStock >= $plannedQty) {
                            // Fully covered by warehouse stock
                            $runningStock[$matId] = $currentStock - $plannedQty;
                            $shortage = 0.0;
                        } else {
                            // Partially or not covered
                            $shortage = $plannedQty - $currentStock;
                            $runningStock[$matId] = 0.0;
                        }

                        if ($shortage <= 0) {
                            continue;
                        }

                        $supplierId = $item->supplier_id;
                        if (! isset($pendingSupplierItems[$supplierId])) {
                            $pendingSupplierItems[$supplierId] = [
                                'project_ids' => [],
                                'items' => [],
                            ];
                        }

                        if ($planning->project_id) {
                            $pendingSupplierItems[$supplierId]['project_ids'][] = $planning->project_id;
                        }

                        $unitPrice = (float) $item->unit_price;
                        $totalPrice = $shortage * $unitPrice;

                        $pendingSupplierItems[$supplierId]['items'][] = [
                            'project_id' => $planning->project_id,
                            'sub_project_id' => $planning->sub_project_id,
                            'material_id' => $item->material_id,
                            'component' => $item->component,
                            'description' => $item->notes ?? 'Raw material',
                            'qty' => $shortage,
                            'unit' => $item->unit ?? 'pcs',
                            'unit_price' => $unitPrice,
                            'total_price' => $totalPrice,
                            'qty_received' => 0,
                        ];
                    }
                }
            }

            // 2. Generate or update Supplier POs
            foreach ($pendingSupplierItems as $supplierId => $data) {
                if (empty($data['items'])) {
                    continue;
                }

                $newProjectIds = array_values(array_unique(array_filter($data['project_ids'])));

                // Check for existing draft PO for this supplier in the company
                $po = PoSupplier::where('company_id', $companyId)
                    ->where('supplier_id', $supplierId)
                    ->where('status', 'draft')
                    ->first();

                if (! $po) {
                    $po = PoSupplier::create([
                        'company_id' => $companyId,
                        'po_number' => CodeGenerator::generatePOSupplierNo(),
                        'project_id' => $newProjectIds[0] ?? null,
                        'project_ids' => $newProjectIds,
                        'supplier_id' => $supplierId,
                        'po_date' => now()->toDateString(),
                        'ppn_percent' => 11,
                        'status' => 'draft',
                    ]);
                    $generatedPoNumbers[] = $po->po_number;
                } else {
                    $existingProjectIds = $po->project_ids ?? ($po->project_id ? [$po->project_id] : []);
                    $mergedProjectIds = array_values(array_unique(array_filter(array_merge($existingProjectIds, $newProjectIds))));
                    $po->update(['project_ids' => $mergedProjectIds]);
                    $generatedPoNumbers[] = $po->po_number.' (updated)';
                }

                foreach ($data['items'] as $itemData) {
                    $itemData['po_supplier_id'] = $po->id;
                    PoSupplierItem::create($itemData);
                }

                $po->recalculateTotals();
                $createdSupplierPos[] = $po;
            }

            // 3. Generate or update Subcon POs
            foreach ($pendingSubconItems as $subconId => $data) {
                if (empty($data['items'])) {
                    continue;
                }

                $newProjectIds = array_values(array_unique(array_filter($data['project_ids'])));

                // Check for existing draft PO for this subcon in the company
                $po = PoSubcon::where('company_id', $companyId)
                    ->where('subcon_id', $subconId)
                    ->where('status', 'draft')
                    ->first();

                if (! $po) {
                    $po = PoSubcon::create([
                        'company_id' => $companyId,
                        'po_number' => CodeGenerator::generatePOSubconNo(),
                        'project_id' => $newProjectIds[0] ?? null,
                        'project_ids' => $newProjectIds,
                        'subcon_id' => $subconId,
                        'po_date' => now()->toDateString(),
                        'status' => 'draft',
                    ]);
                    $generatedPoNumbers[] = $po->po_number;
                } else {
                    $existingProjectIds = $po->project_ids ?? ($po->project_id ? [$po->project_id] : []);
                    $mergedProjectIds = array_values(array_unique(array_filter(array_merge($existingProjectIds, $newProjectIds))));
                    $po->update(['project_ids' => $mergedProjectIds]);
                    $generatedPoNumbers[] = $po->po_number.' (updated)';
                }

                foreach ($data['items'] as $itemData) {
                    $itemData['po_subcon_id'] = $po->id;
                    PoSubconItem::create($itemData);
                }

                $po->recalculateTotals();
                $createdSubconPos[] = $po;
            }

            return [
                'po_numbers' => $generatedPoNumbers,
                'supplier_pos' => $createdSupplierPos,
                'subcon_pos' => $createdSubconPos,
                'processed_count' => $plannings->count(),
                'skipped_items_count' => $skippedItemsCount,
            ];
        });
    }
}
