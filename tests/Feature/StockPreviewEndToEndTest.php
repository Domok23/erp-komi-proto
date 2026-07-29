<?php

namespace Tests\Feature;

use App\Livewire\StockPreviewModal;
use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockPreviewEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_flow_preview_reserve_po(): void
    {
        $company = Company::create(['name' => 'KMT Test', 'code' => 'KMT001']);
        $warehouse = Warehouse::create(['company_id' => $company->id, 'name' => 'Gudang Utama', 'code' => 'WH01']);
        $supplier = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-01', 'name' => 'Supplier Utama']);

        $material = Material::create([
            'code' => 'MAT-100',
            'name' => 'Bahan Kain',
            'category' => 'Bahan Utama',
            'unit' => 'm',
            'supplier_id' => $supplier->id,
            'price' => 50000,
        ]);

        InventoryStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'quantity' => 50,
            'reserved_qty' => 0,
            'available_qty' => 50,
            'unit' => 'm',
        ]);

        $component = Livewire::test(StockPreviewModal::class, [
            'materials' => [
                [
                    'material_id' => $material->id,
                    'code' => $material->code,
                    'name' => $material->name,
                    'quantity_per_unit' => 1.0,
                    'unit' => 'm',
                ],
            ],
            'productionQty' => 100.0,
            'companyId' => $company->id,
        ]);

        $component->assertSet('productionQty', 100.0)
            ->assertSee('Bahan Kain')
            ->assertSee('50.00 m');

        // Test Reserve
        $component->callTableBulkAction('reserve_selected', [$material])
            ->assertDispatched('reserved');

        $this->assertDatabaseHas('material_reservations', [
            'company_id' => $company->id,
            'material_id' => $material->id,
            'reserved_qty' => 50,
        ]);

        // Test Create PO
        $component->callTableBulkAction('create_po_selected', [$material])
            ->assertDispatched('po-created');

        $this->assertDatabaseHas('po_suppliers', [
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'status' => 'draft',
        ]);
    }

    public function test_reserve_selected_shows_warning_when_stock_is_zero(): void
    {
        $company = Company::create(['name' => 'KMT Test 2', 'code' => 'KMT002']);
        $supplier = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-02', 'name' => 'Supplier 2']);

        $material = Material::create([
            'code' => 'MAT-200',
            'name' => 'Benang',
            'category' => 'Bahan Baku',
            'unit' => 'pcs',
            'supplier_id' => $supplier->id,
            'price' => 10000,
        ]);

        $component = Livewire::test(StockPreviewModal::class, [
            'materials' => [
                [
                    'material_id' => $material->id,
                    'code' => $material->code,
                    'name' => $material->name,
                    'quantity_per_unit' => 1.0,
                    'unit' => 'pcs',
                ],
            ],
            'productionQty' => 10.0,
            'companyId' => $company->id,
        ]);

        $component->callTableBulkAction('reserve_selected', [$material]);

        $this->assertDatabaseMissing('material_reservations', [
            'company_id' => $company->id,
            'material_id' => $material->id,
        ]);
    }

    public function test_create_po_selected_shows_info_when_stock_is_sufficient(): void
    {
        $company = Company::create(['name' => 'KMT Test 3', 'code' => 'KMT003']);
        $warehouse = Warehouse::create(['company_id' => $company->id, 'name' => 'Gudang 3', 'code' => 'WH03']);
        $supplier = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-03', 'name' => 'Supplier 3']);

        $material = Material::create([
            'code' => 'MAT-300',
            'name' => 'Kancing',
            'category' => 'Bahan Baku',
            'unit' => 'pcs',
            'supplier_id' => $supplier->id,
            'price' => 500,
        ]);

        InventoryStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'quantity' => 100,
            'reserved_qty' => 0,
            'available_qty' => 100,
            'unit' => 'pcs',
        ]);

        $component = Livewire::test(StockPreviewModal::class, [
            'materials' => [
                [
                    'material_id' => $material->id,
                    'code' => $material->code,
                    'name' => $material->name,
                    'quantity_per_unit' => 1.0,
                    'unit' => 'pcs',
                ],
            ],
            'productionQty' => 10.0,
            'companyId' => $company->id,
        ]);

        $component->callTableBulkAction('create_po_selected', [$material]);

        $this->assertDatabaseMissing('po_suppliers', [
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_reserve_maintains_sufficient_status_and_does_not_flip_to_short(): void
    {
        $company = Company::create(['name' => 'KMT Test 4', 'code' => 'KMT004']);
        $warehouse = Warehouse::create(['company_id' => $company->id, 'name' => 'Gudang 4', 'code' => 'WH04']);
        $supplier = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-04', 'name' => 'Supplier 4']);

        $material = Material::create([
            'code' => 'MAT-400',
            'name' => 'Kain Katun',
            'category' => 'Bahan Baku',
            'unit' => 'm',
            'supplier_id' => $supplier->id,
            'price' => 25000,
        ]);

        InventoryStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'quantity' => 10,
            'reserved_qty' => 0,
            'available_qty' => 10,
            'unit' => 'm',
        ]);

        $component = Livewire::test(StockPreviewModal::class, [
            'materials' => [
                [
                    'material_id' => $material->id,
                    'code' => $material->code,
                    'name' => $material->name,
                    'quantity_per_unit' => 1.0,
                    'unit' => 'm',
                ],
            ],
            'productionQty' => 10.0,
            'companyId' => $company->id,
        ]);

        // Before reserve: status is sufficient
        $previewDataBefore = $component->instance()->getPreviewData()->get($material->id);
        $this->assertSame('sufficient', $previewDataBefore->status);
        $this->assertEquals(0, $previewDataBefore->toBuy);

        // Perform reserve
        $component->callTableBulkAction('reserve_selected', [$material]);

        // After reserve: effective stock includes reserved qty, status remains sufficient, toBuy remains 0!
        $previewDataAfter = $component->instance()->getPreviewData()->get($material->id);
        $this->assertSame('sufficient', $previewDataAfter->status);
        $this->assertEquals(0, $previewDataAfter->toBuy);
    }

    public function test_create_po_prevents_duplicate_po_creation(): void
    {
        $company = Company::create(['name' => 'KMT Test 5', 'code' => 'KMT005']);
        $supplier = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-05', 'name' => 'Supplier 5']);

        $material = Material::create([
            'code' => 'MAT-500',
            'name' => 'Renda',
            'category' => 'Bahan Baku',
            'unit' => 'm',
            'supplier_id' => $supplier->id,
            'price' => 15000,
        ]);

        $component = Livewire::test(StockPreviewModal::class, [
            'materials' => [
                [
                    'material_id' => $material->id,
                    'code' => $material->code,
                    'name' => $material->name,
                    'quantity_per_unit' => 1.0,
                    'unit' => 'm',
                ],
            ],
            'productionQty' => 10.0,
            'companyId' => $company->id,
        ]);

        // Initial state: status short, toBuy 10
        $dataInitial = $component->instance()->getPreviewData()->get($material->id);
        $this->assertSame('short', $dataInitial->status);
        $this->assertEquals(10.0, $dataInitial->toBuy);

        // First PO creation
        $component->callTableBulkAction('create_po_selected', [$material]);
        $this->assertDatabaseCount('po_suppliers', 1);

        // After first PO: status is ordered, toBuy is 0
        $dataAfterPo = $component->instance()->getPreviewData()->get($material->id);
        $this->assertSame('ordered', $dataAfterPo->status);
        $this->assertEquals(0.0, $dataAfterPo->toBuy);

        // Second PO creation attempt (should be skipped and NOT create duplicate PO)
        $component->callTableBulkAction('create_po_selected', [$material]);
        $this->assertDatabaseCount('po_suppliers', 1);
    }

    public function test_create_po_warns_when_material_has_no_supplier(): void
    {
        $company = Company::create(['name' => 'KMT Test 6', 'code' => 'KMT006']);

        $materialNoSupplier = Material::create([
            'code' => 'MAT-600',
            'name' => 'Resleting Tanpa Supplier',
            'category' => 'Aksesoris',
            'unit' => 'pcs',
            'supplier_id' => null,
            'price' => 2000,
        ]);

        $component = Livewire::test(StockPreviewModal::class, [
            'materials' => [
                [
                    'material_id' => $materialNoSupplier->id,
                    'code' => $materialNoSupplier->code,
                    'name' => $materialNoSupplier->name,
                    'quantity_per_unit' => 1.0,
                    'unit' => 'pcs',
                ],
            ],
            'productionQty' => 10.0,
            'companyId' => $company->id,
        ]);

        $component->callTableBulkAction('create_po_selected', [$materialNoSupplier]);

        $this->assertDatabaseCount('po_suppliers', 0);
    }
}
