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

    public function test_full_flow_preview_and_reserve(): void
    {
        $company = Company::create(['name' => 'KMT Test', 'code' => 'KMT001']);
        $warehouse = Warehouse::create(['company_id' => $company->id, 'name' => 'Main Warehouse', 'code' => 'WH01']);
        $supplier = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-01', 'name' => 'Main Supplier']);

        $material = Material::create([
            'code' => 'MAT-100',
            'name' => 'Canvas Fabric',
            'category' => 'Fabric',
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
            ->assertSee('Canvas Fabric')
            ->assertSee('50.00 m');

        // Test Reserve
        $component->callTableBulkAction('reserve_selected', [$material])
            ->assertDispatched('reserved');

        $this->assertDatabaseHas('material_reservations', [
            'company_id' => $company->id,
            'material_id' => $material->id,
            'reserved_qty' => 50,
        ]);
    }

    public function test_reserve_selected_shows_warning_when_stock_is_zero(): void
    {
        $company = Company::create(['name' => 'KMT Test 2', 'code' => 'KMT002']);
        $supplier = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-02', 'name' => 'Supplier 2']);

        $material = Material::create([
            'code' => 'MAT-200',
            'name' => 'Thread',
            'category' => 'Thread',
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

    public function test_reserve_maintains_sufficient_status_and_does_not_flip_to_short(): void
    {
        $company = Company::create(['name' => 'KMT Test 4', 'code' => 'KMT004']);
        $warehouse = Warehouse::create(['company_id' => $company->id, 'name' => 'Warehouse 4', 'code' => 'WH04']);
        $supplier = Supplier::create(['company_id' => $company->id, 'code' => 'SUP-04', 'name' => 'Supplier 4']);

        $material = Material::create([
            'code' => 'MAT-400',
            'name' => 'Cotton Fabric',
            'category' => 'Fabric',
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
}
