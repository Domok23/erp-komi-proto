<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\PoSubcon;
use App\Models\PoSubconItem;
use App\Models\Subcon;
use App\Models\SubconMaterialIn;
use App\Models\SubconMaterialInItem;
use App\Models\SubconMaterialOut;
use App\Models\SubconMaterialOutItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubconIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrated_subcon_material_flow(): void
    {
        // 1. Setup Base Data
        $company = Company::create([
            'name' => 'PT Komitrando Emporio Test',
            'code' => 'KOMI-TEST',
            'address' => 'Jogja',
        ]);

        $subcon = Subcon::create([
            'company_id' => $company->id,
            'name' => 'Test Sewing Subcon',
            'code' => 'SUB-TEST',
            'service_type' => 'sewing',
            'contact_person' => 'Subcon Person',
            'email' => 'subcon@test.com',
            'phone' => '123456',
            'address' => 'Jogja',
            'is_active' => true,
        ]);

        // Raw Material (Fabric)
        $rawMaterial = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-RAW-FABRIC',
            'name' => 'Test Raw Fabric',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 10000,
            'stock' => 0,
        ]);

        // Processed Goods (Sewn Panel)
        $processedGoods = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-SEWN-PANEL',
            'name' => 'Test Sewn Panel',
            'category' => 'semi_finished',
            'unit' => 'pcs',
            'price' => 25000,
            'stock' => 0,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $company->id,
            'code' => 'WH-MAIN',
            'name' => 'Main Warehouse',
            'address' => 'Bantul',
            'is_active' => true,
        ]);

        // Seed initial stock of raw material in Gudang Utama (500 yards)
        $stock = InventoryStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $rawMaterial->id,
            'quantity' => 500,
            'available_qty' => 500,
            'unit' => 'yard',
        ]);
        $rawMaterial->update(['stock' => 500]);

        // 2. Create Subcon Purchase Order
        $poSubcon = PoSubcon::create([
            'company_id' => $company->id,
            'po_number' => 'PO-SUB-TEST-001',
            'subcon_id' => $subcon->id,
            'po_date' => now()->toDateString(),
            'status' => 'ordered',
            'service_cost' => 450000,
            'total_cost' => 450000,
        ]);

        PoSubconItem::create([
            'po_subcon_id' => $poSubcon->id,
            'description' => 'Test Sewing Service',
            'qty' => 90,
            'unit_price' => 5000,
            'total_price' => 450000,
        ]);

        // 3. Send Materials to Subcon (Material Out)
        $materialOut = SubconMaterialOut::create([
            'company_id' => $company->id,
            'po_subcon_id' => $poSubcon->id,
            'subcon_id' => $subcon->id,
            'document_number' => 'MAT-OUT-TEST-001',
            'departure_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $outItem = SubconMaterialOutItem::create([
            'subcon_material_out_id' => $materialOut->id,
            'material_id' => $rawMaterial->id,
            'qty_sent' => 100, // Send 100 yards of raw fabric
            'unit' => 'yard',
        ]);

        // Mark Subcon Material Out as sent -> triggers InventoryService::sendToSubcon
        $materialOut->update(['status' => 'sent']);

        // Verify Raw Material stock decreases by 100 yards (500 -> 400)
        $stock->refresh();
        $this->assertEquals(400, $stock->quantity);
        $this->assertEquals(400, $stock->available_qty);

        // Verify global material stock synced
        $rawMaterial->refresh();
        $this->assertEquals(400, $rawMaterial->stock);

        // Verify movement created
        $outMovement = InventoryMovement::where('reference_type', SubconMaterialOut::class)
            ->where('reference_id', $materialOut->id)
            ->first();
        $this->assertNotNull($outMovement);
        $this->assertEquals(100, $outMovement->quantity);
        $this->assertEquals('production_out', $outMovement->type);

        // 4. Receive Processed Goods & Leftover Raw Materials (Material In)
        $materialIn = SubconMaterialIn::create([
            'company_id' => $company->id,
            'po_subcon_id' => $poSubcon->id,
            'subcon_material_out_id' => $materialOut->id,
            'subcon_id' => $subcon->id,
            'document_number' => 'MAT-IN-TEST-001',
            'receive_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        // Item 1: Processed Goods (Sewn Panels) - received as Description (no material_id)
        $inItem1 = SubconMaterialInItem::create([
            'subcon_material_in_id' => $materialIn->id,
            'material_id' => null,
            'description' => 'Test Sewing Service',
            'item_type' => 'processed',
            'qty_received' => 90,
            'qty_rejected' => 2,
            'unit' => 'pcs',
        ]);

        // Item 2: Leftover Raw Material (Fabric) - 5 yards leftover returned, 3 yards reject
        $inItem2 = SubconMaterialInItem::create([
            'subcon_material_in_id' => $materialIn->id,
            'material_id' => $rawMaterial->id,
            'description' => null,
            'item_type' => 'raw_return',
            'qty_received' => 5,
            'qty_rejected' => 3,
            'unit' => 'yard',
        ]);

        // Mark Subcon Material In as verified -> triggers InventoryService::receiveFromSubcon
        $materialIn->update(['status' => 'verified']);

        // 5. Verifications
        // A. Raw Material Stock should increase by 5 yards (400 -> 405)
        $stock->refresh();
        $this->assertEquals(405, $stock->quantity);
        $rawMaterial->refresh();
        $this->assertEquals(405, $rawMaterial->stock);

        // B. Processed Goods (Sewn Panels) stock should NOT change because it was received as service description
        $processedStock = InventoryStock::where('warehouse_id', $warehouse->id)
            ->where('material_id', $processedGoods->id)
            ->first();
        $this->assertNull($processedStock);

        // C. Check Inventory Movements for Material In
        // Processed goods movement should NOT exist
        $moveProcessed = InventoryMovement::where('reference_type', SubconMaterialIn::class)
            ->where('reference_id', $materialIn->id)
            ->where('material_id', $processedGoods->id)
            ->first();
        $this->assertNull($moveProcessed);

        // Raw return movement (qty_received > 0) should exist
        $moveRawReturn = InventoryMovement::where('reference_type', SubconMaterialIn::class)
            ->where('reference_id', $materialIn->id)
            ->where('material_id', $rawMaterial->id)
            ->first();
        $this->assertNotNull($moveRawReturn);
        $this->assertEquals(5, $moveRawReturn->quantity);
        $this->assertEquals('production_in', $moveRawReturn->type);
        $this->assertStringContainsString('Leftover raw material returned', $moveRawReturn->notes);
    }
}
