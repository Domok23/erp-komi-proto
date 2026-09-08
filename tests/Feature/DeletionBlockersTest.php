<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\InvoicePurchase;
use App\Models\InvoiceSales;
use App\Models\JobOrder;
use App\Models\Material;
use App\Models\MerchandisePlanning;
use App\Models\PoSubcon;
use App\Models\PoSupplier;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\RdDesign;
use App\Models\SalesOrder;
use App\Models\Subcon;
use App\Models\SubProject;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeletionBlockersTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'PT Komi Test', 'code' => 'KOMI', 'address' => 'Jakarta']);
    }

    public function test_sub_project_deletion_blockers(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-SP-01',
            'name' => 'Project SP Test',
            'type' => 'mass',
            'status' => 'planning',
            'target_qty' => 100,
        ]);

        $sp = SubProject::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'name' => 'Variant A',
            'target_qty' => 50,
        ]);

        $this->assertEmpty($sp->getDeletionBlockers());

        MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'sub_project_id' => $sp->id,
            'version' => 1,
            'status' => 'draft',
        ]);

        $blockers = $sp->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('Merchandise Planning', $blockers[0]);
    }

    public function test_chart_of_account_deletion_blockers(): void
    {
        $parent = ChartOfAccount::create([
            'company_id' => $this->company->id,
            'account_code' => '1000',
            'account_name' => 'Assets',
            'account_type' => 'asset',
        ]);

        $this->assertEmpty($parent->getDeletionBlockers());

        ChartOfAccount::create([
            'company_id' => $this->company->id,
            'account_code' => '1100',
            'account_name' => 'Current Assets',
            'account_type' => 'asset',
            'parent_id' => $parent->id,
        ]);

        $blockers = $parent->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('Sub-Account(s)', $blockers[0]);
    }

    public function test_customer_deletion_blockers(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'code' => 'CUST-001',
            'name' => 'Test Customer',
        ]);

        $this->assertEmpty($customer->getDeletionBlockers());

        Project::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'project_code' => 'PRJ-CUST-01',
            'name' => 'Project Cust Test',
            'type' => 'proto',
            'status' => 'planning',
            'target_qty' => 10,
        ]);

        $blockers = $customer->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('Project(s)', $blockers[0]);
    }

    public function test_supplier_deletion_blockers(): void
    {
        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'code' => 'SUP-001',
            'name' => 'Test Supplier',
        ]);

        $this->assertEmpty($supplier->getDeletionBlockers());

        Material::create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'code' => 'MAT-SUP-01',
            'name' => 'Material Sup Test',
            'category' => 'fabric',
            'unit' => 'pcs',
            'price' => 1000,
        ]);

        $blockers = $supplier->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('Material(s)', $blockers[0]);
    }

    public function test_subcon_deletion_blockers(): void
    {
        $subcon = Subcon::create([
            'company_id' => $this->company->id,
            'code' => 'SUBCON-001',
            'name' => 'Test Subcon',
            'service_type' => 'sewing',
        ]);

        $this->assertEmpty($subcon->getDeletionBlockers());

        PoSubcon::create([
            'company_id' => $this->company->id,
            'subcon_id' => $subcon->id,
            'po_number' => 'PO-SUB-001',
            'po_date' => now(),
            'status' => 'draft',
        ]);

        $blockers = $subcon->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('PO Subcon', $blockers[0]);
    }

    public function test_warehouse_deletion_blockers(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'code' => 'WH-001',
            'name' => 'Main Warehouse',
        ]);

        $this->assertEmpty($warehouse->getDeletionBlockers());

        $mat = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-WH-01',
            'name' => 'Material WH Test',
            'category' => 'fabric',
            'unit' => 'pcs',
            'price' => 500,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $mat->id,
            'quantity' => 100,
        ]);

        $blockers = $warehouse->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('stock', $blockers[0]);
    }

    public function test_material_deletion_blockers(): void
    {
        $material = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-TEST-01',
            'name' => 'Test Material Block',
            'category' => 'fabric',
            'unit' => 'pcs',
            'price' => 500,
        ]);

        $this->assertEmpty($material->getDeletionBlockers());

        $wh = Warehouse::create([
            'company_id' => $this->company->id,
            'code' => 'WH-MAT',
            'name' => 'WH Mat',
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $wh->id,
            'material_id' => $material->id,
            'quantity' => 50,
        ]);

        $blockers = $material->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsStringIgnoringCase('stock', $blockers[0]);
    }

    public function test_rd_design_deletion_blockers(): void
    {
        $design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'RD-001',
            'name' => 'Design Test',
            'product_type' => 'backpack',
            'status' => 'draft',
        ]);

        $this->assertEmpty($design->getDeletionBlockers());

        Project::create([
            'company_id' => $this->company->id,
            'design_id' => $design->id,
            'project_code' => 'PRJ-RD-01',
            'name' => 'Project RD Test',
            'type' => 'sample',
            'status' => 'planning',
            'target_qty' => 10,
        ]);

        $blockers = $design->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('Project(s)', $blockers[0]);
    }

    public function test_sales_order_deletion_blockers(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'code' => 'CUST-SO',
            'name' => 'SO Customer',
        ]);

        $project = Project::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'project_code' => 'PRJ-SO-01',
            'name' => 'Project for SO',
            'type' => 'mass',
            'status' => 'planning',
            'target_qty' => 100,
        ]);

        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'so_number' => 'SO-001',
            'order_date' => now(),
            'status' => 'draft',
        ]);

        $this->assertEmpty($so->getDeletionBlockers());

        $so->update(['status' => 'confirmed']);
        $blockers = $so->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString("status is 'confirmed'", $blockers[0]);

        $so->update(['status' => 'draft']);
        InvoiceSales::create([
            'company_id' => $this->company->id,
            'sales_order_id' => $so->id,
            'invoice_number' => 'INV-SO-01',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 1000,
            'grand_total' => 1000,
            'status' => 'unpaid',
        ]);

        $blockersWithInv = $so->getDeletionBlockers();
        $this->assertNotEmpty($blockersWithInv);
        $this->assertStringContainsString('Sales Invoice(s)', $blockersWithInv[0]);
    }

    public function test_production_order_deletion_blockers(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-PO-01',
            'name' => 'PO Project',
            'type' => 'mass',
            'status' => 'in_production',
            'target_qty' => 100,
        ]);

        $planning = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'version' => 1,
            'status' => 'draft',
        ]);

        $po = ProductionOrder::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'merchandising_planning_id' => $planning->id,
            'production_number' => 'PO-PROD-001',
            'status' => 'planned',
            'planned_qty' => 100,
        ]);

        $this->assertEmpty($po->getDeletionBlockers());

        $po->update(['status' => 'in_progress']);
        $blockers = $po->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString("status is 'in_progress'", $blockers[0]);

        $po->update(['status' => 'planned']);
        JobOrder::create([
            'company_id' => $this->company->id,
            'production_order_id' => $po->id,
            'merchandising_planning_id' => $planning->id,
            'job_order_number' => 'JO-001',
            'task_type' => 'cutting',
            'status' => 'pending',
        ]);

        $blockersWithJo = $po->getDeletionBlockers();
        $this->assertNotEmpty($blockersWithJo);
        $this->assertStringContainsString('Job Order(s)', $blockersWithJo[0]);
    }

    public function test_po_supplier_deletion_blockers(): void
    {
        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'code' => 'SUP-PO-01',
            'name' => 'Supplier PO Test',
        ]);

        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'POS-001',
            'po_date' => now(),
            'status' => 'draft',
            'approval_status' => 'draft',
        ]);

        $this->assertEmpty($po->getDeletionBlockers());

        $po->update(['status' => 'ordered']);
        $blockers = $po->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString("status is 'ordered'", $blockers[0]);

        $po->update(['status' => 'draft']);
        InvoicePurchase::create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'purchase_type' => 'po_supplier',
            'reference_id' => $po->id,
            'invoice_number' => 'INV-PUR-01',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 1000,
            'grand_total' => 1000,
            'status' => 'unpaid',
        ]);

        $blockersWithInv = $po->getDeletionBlockers();
        $this->assertNotEmpty($blockersWithInv);
        $this->assertStringContainsString('Purchase Invoice(s)', $blockersWithInv[0]);
    }

    public function test_po_subcon_deletion_blockers(): void
    {
        $subcon = Subcon::create([
            'company_id' => $this->company->id,
            'code' => 'SUB-PO-01',
            'name' => 'Subcon PO Test',
            'service_type' => 'sewing',
        ]);

        $po = PoSubcon::create([
            'company_id' => $this->company->id,
            'subcon_id' => $subcon->id,
            'po_number' => 'POSUB-001',
            'po_date' => now(),
            'status' => 'draft',
        ]);

        $this->assertEmpty($po->getDeletionBlockers());

        $po->update(['status' => 'ordered']);
        $blockers = $po->getDeletionBlockers();
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString("status is 'ordered'", $blockers[0]);

        $po->update(['status' => 'draft']);
        InvoicePurchase::create([
            'company_id' => $this->company->id,
            'subcon_id' => $subcon->id,
            'purchase_type' => 'po_subcon',
            'reference_id' => $po->id,
            'invoice_number' => 'INV-SUB-01',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 1000,
            'grand_total' => 1000,
            'status' => 'unpaid',
        ]);

        $blockersWithInv = $po->getDeletionBlockers();
        $this->assertNotEmpty($blockersWithInv);
        $this->assertStringContainsString('Purchase Invoice(s)', $blockersWithInv[0]);
    }
}
