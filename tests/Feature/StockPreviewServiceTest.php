<?php

namespace Tests\Feature;

use App\DTOs\StockPreviewData;
use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\Warehouse;
use App\Services\StockCalculator;
use App\Services\StockPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockPreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_aggregates_stock_across_warehouses(): void
    {
        $company = Company::create(['name' => 'KMT Test', 'code' => 'KMT001']);
        $warehouse1 = Warehouse::create(['company_id' => $company->id, 'name' => 'Gudang Utama', 'code' => 'WH01']);
        $warehouse2 = Warehouse::create(['company_id' => $company->id, 'name' => 'Gudang Bahan', 'code' => 'WH02']);

        $material = Material::create([
            'code' => 'MAT-001',
            'name' => 'Nylon Thread',
            'category' => 'Bahan Baku',
            'unit' => 'kg',
        ]);

        InventoryStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse1->id,
            'material_id' => $material->id,
            'quantity' => 30,
            'reserved_qty' => 0,
            'available_qty' => 30,
            'unit' => 'kg',
        ]);
        InventoryStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse2->id,
            'material_id' => $material->id,
            'quantity' => 20,
            'reserved_qty' => 0,
            'available_qty' => 20,
            'unit' => 'kg',
        ]);

        $service = new StockPreviewService(new StockCalculator);
        $result = $service->preview(
            materials: [
                [
                    'material_id' => $material->id,
                    'code' => $material->code,
                    'name' => $material->name,
                    'quantity_per_unit' => 0.5,
                    'unit' => 'kg',
                ],
            ],
            productionQty: 100.0,
            companyId: $company->id,
        );

        $this->assertCount(1, $result);
        $this->assertInstanceOf(StockPreviewData::class, $result->first());
        $this->assertSame(50.0, $result->first()->required);
        $this->assertSame(50.0, $result->first()->currentStock);
        $this->assertSame(0.0, $result->first()->toBuy);
        $this->assertSame('sufficient', $result->first()->status);
    }
}
