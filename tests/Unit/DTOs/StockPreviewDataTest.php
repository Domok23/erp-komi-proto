<?php

namespace Tests\Unit\DTOs;

use App\DTOs\StockPreviewData;
use PHPUnit\Framework\TestCase;

class StockPreviewDataTest extends TestCase
{
    public function test_it_creates_with_all_fields(): void
    {
        $data = new StockPreviewData(
            materialId: 1,
            materialCode: 'MAT-001',
            materialName: 'Nylon Thread',
            unit: 'kg',
            quantityPerUnit: 0.5,
            productionQty: 100.0,
            required: 50.0,
            currentStock: 30.0,
            toBuy: 20.0,
            status: 'short',
            companyId: 1,
        );

        $this->assertSame(1, $data->materialId);
        $this->assertSame('MAT-001', $data->materialCode);
        $this->assertSame(50.0, $data->required);
        $this->assertSame('short', $data->status);
        $this->assertTrue($data->needsPurchase());
        $this->assertFalse($data->isSufficient());
    }
}
