<?php

namespace App\Services;

use App\DTOs\StockPreviewData;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\MaterialReservation;
use App\Models\PoSupplier;
use App\Models\PoSupplierItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockPreviewService
{
    public function __construct(
        private StockCalculator $calculator,
    ) {}

    public function preview(array $materials, float $productionQty, int $companyId): Collection
    {
        $materialIds = collect($materials)->pluck('material_id')->unique()->filter()->all();

        $stocks = InventoryStock::where('company_id', $companyId)
            ->whereIn('material_id', $materialIds)
            ->get()
            ->groupBy('material_id')
            ->map(fn ($group) => (float) $group->sum('available_qty'));

        return collect($materials)->map(function ($material) use ($stocks, $productionQty, $companyId) {
            $calc = $this->calculator->calculate(
                materialId: (int) $material['material_id'],
                quantityPerUnit: (float) $material['quantity_per_unit'],
                productionQty: $productionQty,
                currentStock: (float) ($stocks[$material['material_id']] ?? 0),
            );

            return new StockPreviewData(
                materialId: (int) $material['material_id'],
                materialCode: $material['code'] ?? '',
                materialName: $material['name'] ?? '',
                unit: $material['unit'] ?? 'pcs',
                quantityPerUnit: (float) $material['quantity_per_unit'],
                productionQty: $productionQty,
                required: $calc['required'],
                currentStock: $calc['current_stock'],
                toBuy: $calc['to_buy'],
                status: $calc['status'],
                companyId: $companyId,
            );
        });
    }

    public function reserve(array $reservations, int $companyId): Collection
    {
        return DB::transaction(function () use ($reservations, $companyId) {
            $created = collect();

            foreach ($reservations as $item) {
                $stock = InventoryStock::where('company_id', $companyId)
                    ->where('material_id', $item['material_id'])
                    ->where('available_qty', '>=', $item['qty'])
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    throw new \Exception("Insufficient stock for material {$item['material_id']}");
                }

                $stock->reserved_qty += $item['qty'];
                $stock->available_qty -= $item['qty'];
                $stock->save();

                $reservation = MaterialReservation::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $stock->warehouse_id,
                    'material_id' => $item['material_id'],
                    'document_number' => CodeGenerator::generateReservationNumber(),
                    'reserved_qty' => $item['qty'],
                    'status' => 'approved',
                    'reservation_date' => now(),
                ]);

                $created->push($reservation);
            }

            return $created;
        });
    }

    public function createPurchaseOrder(array $materials, int $companyId, ?int $projectId = null): Collection
    {
        $materialIds = collect($materials)->pluck('material_id')->unique()->all();
        $materialsDb = Material::whereIn('id', $materialIds)->get()->keyBy('id');

        $materialsWithSupplier = collect($materials)
            ->map(function ($item) use ($materialsDb) {
                $mat = $materialsDb->get($item['material_id']);

                return array_merge($item, [
                    'supplier_id' => $mat?->supplier_id,
                    'price' => $mat?->price ?? 0,
                    'unit' => $item['unit'] ?? $mat?->unit ?? 'pcs',
                ]);
            })
            ->filter(fn ($item) => ! empty($item['supplier_id']))
            ->groupBy('supplier_id');

        return DB::transaction(function () use ($materialsWithSupplier, $companyId, $projectId) {
            return $materialsWithSupplier->map(function ($items, $supplierId) use ($companyId, $projectId) {
                $po = PoSupplier::create([
                    'company_id' => $companyId,
                    'po_number' => CodeGenerator::generatePOSupplierNo(),
                    'supplier_id' => (int) $supplierId,
                    'project_id' => $projectId,
                    'po_date' => now(),
                    'status' => 'draft',
                ]);

                foreach ($items as $item) {
                    PoSupplierItem::create([
                        'po_supplier_id' => $po->id,
                        'material_id' => (int) $item['material_id'],
                        'qty' => (float) $item['qty'],
                        'unit' => $item['unit'] ?? 'pcs',
                        'unit_price' => (float) ($item['price'] ?? 0),
                        'total_price' => (float) $item['qty'] * (float) ($item['price'] ?? 0),
                    ]);
                }

                $po->recalculateTotals();

                return $po;
            })->values();
        });
    }
}
