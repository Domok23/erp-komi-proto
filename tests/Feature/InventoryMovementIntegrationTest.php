<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceiptRetur;
use App\Models\GoodsReceiptReturItem;
use App\Models\InventoryStock;
use App\Models\JobOrder;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\MerchandisePlanning;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\RdDesign;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Warehouse $warehouse;

    private Material $material;

    private InventoryStock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST-COMP',
            'address' => 'Test Address',
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'code' => 'WH-MAIN',
            'name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        $this->material = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-RAW',
            'name' => 'Raw Cotton Fabric',
            'category' => 'fabric',
            'unit' => 'meter',
            'price' => 1000,
        ]);

        $this->stock = InventoryStock::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'material_id' => $this->material->id,
            'quantity' => 500.00,
            'reserved_qty' => 0.00,
            'available_qty' => 500.00,
            'unit' => 'meter',
        ]);
    }

    public function test_goods_receipt_return_reduces_stock_on_verification(): void
    {
        $goodsReceipt = GoodsReceipt::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'gr_number' => 'GR-001',
            'po_type' => 'supplier',
            'po_id' => 1,
            'status' => 'draft',
            'receipt_date' => now(),
        ]);

        GoodsReceiptItem::create([
            'goods_receipt_id' => $goodsReceipt->id,
            'material_id' => $this->material->id,
            'qty_ordered' => 100,
            'qty_received' => 100,
            'qty_rejected' => 0,
            'unit' => 'meter',
        ]);

        $retur = GoodsReceiptRetur::create([
            'goods_receipt_id' => $goodsReceipt->id,
            'retur_number' => 'RET-001',
            'status' => 'draft',
            'notes' => 'Damaged fabrics',
        ]);

        $returItem = GoodsReceiptReturItem::create([
            'goods_receipt_retur_id' => $retur->id,
            'material_id' => $this->material->id,
            'qty_returned' => 30.00,
            'reason' => 'Defect',
        ]);

        // Verify stock is still 500
        $this->assertEquals(500.00, (float) $this->stock->fresh()->quantity);

        // Transition to verified
        $retur->update(['status' => 'verified']);

        // Verify stock reduced by 30 (500 - 30 = 470)
        $this->assertEquals(470.00, (float) $this->stock->fresh()->quantity);

        // Verify movement logged
        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'return_out',
            'reference_type' => GoodsReceiptRetur::class,
            'reference_id' => $retur->id,
            'quantity' => 30.00,
            'before_qty' => 500.00,
            'after_qty' => 470.00,
        ]);

        // Verify deletion reverses stock
        $retur->delete();
        $this->assertEquals(500.00, (float) $this->stock->fresh()->quantity);
    }

    public function test_material_usage_reduces_stock_on_completion(): void
    {
        $design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'DSN-EBP-001',
            'name' => 'Explorer Jacket Pro',
            'product_type' => 'jacket',
            'status' => 'approved',
        ]);

        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-001',
            'name' => 'Explorer Project',
            'design_id' => $design->id,
            'target_qty' => 100,
        ]);

        $planning = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'design_id' => $design->id,
            'planning_date' => now(),
            'status' => 'finalised',
        ]);

        $po = ProductionOrder::create([
            'company_id' => $this->company->id,
            'production_number' => 'PO-001',
            'project_id' => $project->id,
            'merchandising_planning_id' => $planning->id,
            'planned_qty' => 100,
            'completed_qty' => 0,
            'status' => 'planned',
        ]);

        $jobOrder = JobOrder::create([
            'company_id' => $this->company->id,
            'production_order_id' => $po->id,
            'merchandising_planning_id' => $planning->id,
            'job_order_number' => 'JO-001',
            'task_type' => 'cutting',
            'planned_qty' => 100,
            'status' => 'pending',
        ]);

        $usage = MaterialUsage::create([
            'company_id' => $this->company->id,
            'job_order_id' => $jobOrder->id,
            'material_id' => $this->material->id,
            'usage_date' => now(),
            'planned_qty' => 50.00,
            'actual_qty' => 45.00,
            'waste_qty' => 5.00,
            'status' => 'planned',
        ]);

        // Verify stock is still 500
        $this->assertEquals(500.00, (float) $this->stock->fresh()->quantity);

        // Complete usage
        $usage->update(['status' => 'completed']);

        // Verify stock reduced by 45 (500 - 45 = 455)
        $this->assertEquals(455.00, (float) $this->stock->fresh()->quantity);

        // Verify movement logged
        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'production_out',
            'reference_type' => MaterialUsage::class,
            'reference_id' => $usage->id,
            'quantity' => 45.00,
            'before_qty' => 500.00,
            'after_qty' => 455.00,
        ]);

        // Verify reversion on status change back to planned
        $usage->update(['status' => 'planned']);
        $this->assertEquals(500.00, (float) $this->stock->fresh()->quantity);
    }

    public function test_production_order_completion_adds_finished_goods_stock(): void
    {
        $design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'DSN-EBP-001',
            'name' => 'Explorer Jacket Pro',
            'product_type' => 'jacket',
            'status' => 'approved',
            'estimated_selling_price' => 150000,
        ]);

        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-001',
            'name' => 'Explorer Project',
            'design_id' => $design->id,
            'target_qty' => 100,
        ]);

        $planning = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'design_id' => $design->id,
            'planning_date' => now(),
            'status' => 'finalised',
        ]);

        $po = ProductionOrder::create([
            'company_id' => $this->company->id,
            'production_number' => 'PO-001',
            'project_id' => $project->id,
            'merchandising_planning_id' => $planning->id,
            'planned_qty' => 100,
            'completed_qty' => 95,
            'status' => 'planned',
        ]);

        // Verify no finished stock exists yet
        $finishedMaterial = Material::where('code', $design->code)->first();
        $this->assertNull($finishedMaterial);

        // Complete Production Order
        $po->update(['status' => 'completed']);

        // Verify finished product material created
        $finishedMaterial = Material::where('code', $design->code)->first();
        $this->assertNotNull($finishedMaterial);
        $this->assertEquals('finished', $finishedMaterial->category);

        // Verify stock created and quantity matches completed_qty (95)
        $finishedStock = InventoryStock::where('material_id', $finishedMaterial->id)->first();
        $this->assertNotNull($finishedStock);
        $this->assertEquals(95.00, (float) $finishedStock->quantity);

        // Verify movement logged
        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'production_in',
            'reference_type' => ProductionOrder::class,
            'reference_id' => $po->id,
            'quantity' => 95.00,
            'before_qty' => 0.00,
            'after_qty' => 95.00,
        ]);
    }

    public function test_shipment_reduces_finished_goods_stock(): void
    {
        $design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'DSN-EBP-001',
            'name' => 'Explorer Jacket Pro',
            'product_type' => 'jacket',
            'status' => 'approved',
            'estimated_selling_price' => 150000,
        ]);

        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-001',
            'name' => 'Explorer Project',
            'design_id' => $design->id,
            'target_qty' => 100,
        ]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'code' => 'CUST-01',
            'name' => 'Explorer Store',
        ]);

        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-001',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'quantity' => 50,
            'unit_price' => 150000,
            'subtotal' => 7500000,
            'grand_total' => 7500000,
        ]);

        // Create finished product Material & Stock manually first
        $finishedMaterial = Material::create([
            'company_id' => $this->company->id,
            'code' => $design->code,
            'name' => $design->name,
            'category' => 'finished',
            'unit' => 'pcs',
        ]);

        $finishedStock = InventoryStock::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'material_id' => $finishedMaterial->id,
            'quantity' => 100.00,
            'reserved_qty' => 0.00,
            'available_qty' => 100.00,
            'unit' => 'pcs',
        ]);

        $shipment = Shipment::create([
            'company_id' => $this->company->id,
            'sales_order_id' => $so->id,
            'shipment_number' => 'SHP-001',
            'shipment_date' => now(),
            'status' => 'pending',
        ]);

        // Verify stock is still 100
        $this->assertEquals(100.00, (float) $finishedStock->fresh()->quantity);

        // Update shipment status to in_transit
        $shipment->update(['status' => 'in_transit']);

        // Verify stock reduced by 50 (100 - 50 = 50)
        $this->assertEquals(50.00, (float) $finishedStock->fresh()->quantity);

        // Verify movement logged
        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'shipment',
            'reference_type' => Shipment::class,
            'reference_id' => $shipment->id,
            'quantity' => 50.00,
            'before_qty' => 100.00,
            'after_qty' => 50.00,
        ]);
    }
}
