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
}
