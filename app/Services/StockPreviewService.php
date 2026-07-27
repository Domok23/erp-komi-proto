<?php

namespace App\Services;

use App\DTOs\StockPreviewData;
use App\Models\InventoryStock;
use Illuminate\Support\Collection;

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
}
