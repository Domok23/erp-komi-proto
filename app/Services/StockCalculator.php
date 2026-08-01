<?php

namespace App\Services;

class StockCalculator
{
    public function calculate(
        int $materialId,
        float $quantityPerUnit,
        float $productionQty,
        float $currentStock,
        float $onOrder = 0.0,
    ): array {
        $required = round($quantityPerUnit * $productionQty, 4);
        $toBuy = max(0.0, round($required - $currentStock - $onOrder, 4));

        $status = match (true) {
            $toBuy === 0.0 && $onOrder > 0 => 'ordered',
            $toBuy === 0.0 => 'sufficient',
            $toBuy >= $required => 'short',
            default => 'partial',
        };

        return [
            'material_id' => $materialId,
            'required' => $required,
            'to_buy' => $toBuy,
            'current_stock' => $currentStock,
            'on_order' => $onOrder,
            'status' => $status,
        ];
    }
}
