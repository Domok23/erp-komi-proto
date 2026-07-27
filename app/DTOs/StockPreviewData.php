<?php

namespace App\DTOs;

class StockPreviewData
{
    public function __construct(
        public readonly int $materialId,
        public readonly string $materialCode,
        public readonly string $materialName,
        public readonly string $unit,
        public readonly float $quantityPerUnit,
        public readonly float $productionQty,
        public readonly float $required,
        public readonly float $currentStock,
        public readonly float $toBuy,
        public readonly string $status,
        public readonly int $companyId,
    ) {}

    public function isSufficient(): bool
    {
        return $this->status === 'sufficient';
    }

    public function needsPurchase(): bool
    {
        return in_array($this->status, ['short', 'partial'], true);
    }
}
