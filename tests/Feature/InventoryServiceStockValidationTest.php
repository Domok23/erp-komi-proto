<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\JobOrder;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\MerchandisePlanning;
use App\Models\PoSubcon;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\RdDesign;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\Subcon;
use App\Models\SubconMaterialOut;
use App\Models\SubconMaterialOutItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceStockValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Warehouse $warehouse;

    protected Material $material;

    protected InventoryStock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC',
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
            'code' => 'MAT-VAL-01',
            'name' => 'Cotton Twill',
            'category' => 'fabric',
            'uom' => 'meter',
            'price' => 25000,
        ]);

        $this->stock = InventoryStock::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'material_id' => $this->material->id,
            'quantity' => 50.00,
            'reserved_qty' => 0.00,
            'available_qty' => 50.00,
            'unit' => 'meter',
        ]);
    }

    public function test_process_material_usage_throws_exception_when_stock_insufficient(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-VAL-02',
            'name' => 'Validation Project 2',
            'target_qty' => 100,
        ]);

        $planning = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'planning_date' => now(),
            'status' => 'finalised',
        ]);

        $po = ProductionOrder::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'merchandising_planning_id' => $planning->id,
            'production_number' => 'PO-VAL-01',
            'planned_qty' => 100,
            'completed_qty' => 0,
            'status' => 'planned',
        ]);

        $jobOrder = JobOrder::create([
            'company_id' => $this->company->id,
            'production_order_id' => $po->id,
            'merchandising_planning_id' => $planning->id,
            'job_order_number' => 'JO-VAL-01',
            'task_type' => 'cutting',
            'planned_qty' => 100,
            'status' => 'in_progress',
        ]);

        $usage = MaterialUsage::create([
            'company_id' => $this->company->id,
            'job_order_id' => $jobOrder->id,
            'material_id' => $this->material->id,
            'usage_date' => now(),
            'planned_qty' => 80.00,
            'actual_qty' => 80.00, // Stock is only 50.00
            'waste_qty' => 0.00,
            'status' => 'planned',
        ]);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage("Insufficient stock for 'Cotton Twill'");

        $usage->update(['status' => 'completed']);
    }

    public function test_process_shipment_throws_exception_when_finished_goods_stock_insufficient(): void
    {
        $design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'DSN-VAL-01',
            'name' => 'Validation Bag',
            'product_type' => 'backpack',
            'status' => 'approved',
        ]);

        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-VAL-01',
            'name' => 'Validation Project',
            'design_id' => $design->id,
            'target_qty' => 100,
        ]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'code' => 'CUST-VAL-01',
            'name' => 'Val Customer',
        ]);

        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-VAL-01',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'quantity' => 100, // Demands 100 pcs, but finished stock is 0
            'unit_price' => 50000,
            'subtotal' => 5000000,
            'grand_total' => 5000000,
        ]);

        $shipment = Shipment::create([
            'company_id' => $this->company->id,
            'sales_order_id' => $so->id,
            'shipment_number' => 'SHP-VAL-001',
            'shipment_date' => now(),
            'status' => 'pending',
        ]);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage("Insufficient stock for 'Validation Bag'");

        $shipment->update(['status' => 'in_transit']);
    }

    public function test_send_to_subcon_throws_exception_when_stock_insufficient(): void
    {
        $subcon = Subcon::create([
            'company_id' => $this->company->id,
            'code' => 'SBC-VAL-01',
            'name' => 'Subcon Val',
            'service_type' => 'sewing',
        ]);

        $poSubcon = PoSubcon::create([
            'company_id' => $this->company->id,
            'subcon_id' => $subcon->id,
            'po_number' => 'POS-VAL-01',
            'po_date' => now()->toDateString(),
            'status' => 'approved',
        ]);

        $out = SubconMaterialOut::create([
            'company_id' => $this->company->id,
            'po_subcon_id' => $poSubcon->id,
            'subcon_id' => $subcon->id,
            'document_number' => 'OUT-VAL-01',
            'departure_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        SubconMaterialOutItem::create([
            'subcon_material_out_id' => $out->id,
            'material_id' => $this->material->id,
            'qty_sent' => 75.00, // Stock is only 50.00
            'unit' => 'meter',
        ]);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage("Insufficient stock for 'Cotton Twill'");

        $out->update(['status' => 'sent']);
    }
}
