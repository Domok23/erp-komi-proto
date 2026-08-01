<?php

namespace Tests\Feature;

use App\DTOs\StockPreviewData;
use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\PoSupplierItem;
use App\Models\Supplier;
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

    public function test_reserve_creates_material_reservation(): void
    {
        $company = Company::create(['name' => 'KMT Test', 'code' => 'KMT001']);
        $warehouse = Warehouse::create(['company_id' => $company->id, 'name' => 'Gudang Utama', 'code' => 'WH01']);
        $material = Material::create([
            'code' => 'MAT-001',
            'name' => 'Nylon Thread',
            'category' => 'Bahan Baku',
            'unit' => 'kg',
        ]);

        $stock = InventoryStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'quantity' => 100,
            'available_qty' => 100,
            'reserved_qty' => 0,
            'unit' => 'kg',
        ]);

        $service = new StockPreviewService(new StockCalculator);
        $result = $service->reserve(
            reservations: [['material_id' => $material->id, 'qty' => 30]],
            companyId: $company->id,
        );

        $this->assertCount(1, $result);
        $stock->refresh();
        $this->assertSame(30.0, (float) $stock->reserved_qty);
        $this->assertSame(70.0, (float) $stock->available_qty);
    }

    public function test_create_purchase_order_groups_by_supplier(): void
    {
        $company = Company::create(['name' => 'KMT Test', 'code' => 'KMT001']);

        $supplierA = Supplier::create([
            'company_id' => $company->id,
            'code' => 'SUP-A',
            'name' => 'Supplier A',
        ]);
        $supplierB = Supplier::create([
            'company_id' => $company->id,
            'code' => 'SUP-B',
            'name' => 'Supplier B',
        ]);

        $mat1 = Material::create([
            'code' => 'MAT-101',
            'name' => 'Zippper',
            'category' => 'Aksesori',
            'unit' => 'pcs',
            'supplier_id' => $supplierA->id,
            'price' => 100,
        ]);
        $mat2 = Material::create([
            'code' => 'MAT-102',
            'name' => 'Button',
            'category' => 'Aksesori',
            'unit' => 'pcs',
            'supplier_id' => $supplierA->id,
            'price' => 50,
        ]);
        $mat3 = Material::create([
            'code' => 'MAT-103',
            'name' => 'Leather',
            'category' => 'Bahan Utama',
            'unit' => 'm',
            'supplier_id' => $supplierB->id,
            'price' => 200,
        ]);

        $service = new StockPreviewService(new StockCalculator);
        $result = $service->createPurchaseOrder(
            materials: [
                ['material_id' => $mat1->id, 'qty' => 10],
                ['material_id' => $mat2->id, 'qty' => 20],
                ['material_id' => $mat3->id, 'qty' => 5],
            ],
            companyId: $company->id,
        );

        $this->assertCount(2, $result);
        $this->assertSame(3, PoSupplierItem::count());
    }
}
