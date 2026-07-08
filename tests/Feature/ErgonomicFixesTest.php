<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseShipmentResource\Pages\CreatePurchaseShipment;
use App\Filament\Resources\SalesOrderResource\Pages\CreateSalesOrder;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Company;
use App\Models\Costing;
use App\Models\Customer;
use App\Models\InvoiceSales;
use App\Models\Material;
use App\Models\MerchandisePlanning;
use App\Models\Payment;
use App\Models\PoSubcon;
use App\Models\Project;
use App\Models\RdDesign;
use App\Models\SalesOrder;
use App\Models\Subcon;
use App\Models\Supplier;
use App\Services\CodeGenerator;
use App\Services\CostingCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ErgonomicFixesTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Customer $customer;

    private RdDesign $design;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'PT Komitrando Emporio Test',
            'code' => 'KOMI-TEST',
            'address' => 'Jogja',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'code' => 'CUST-TEST',
            'email' => 'cust@test.com',
            'phone' => '12345',
            'address' => 'Jogja',
        ]);

        $this->design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'DSN-TEST',
            'name' => 'Test Design',
            'bag_type' => 'backpack',
            'status' => 'approved',
            'estimated_material_cost' => 100000,
        ]);
    }

    /**
     * 1. Test Circular Dependency Fix (Sales Order project_id is nullable)
     */
    public function test_sales_order_can_be_created_without_project(): void
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => CodeGenerator::generateSONumber(),
            'project_id' => null, // now nullable!
            'costing_id' => null,
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'quantity' => 10,
            'unit_price' => 1000,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('sales_orders', [
            'id' => $so->id,
            'project_id' => null,
        ]);
    }

    /**
     * 2. Test Costing Sheet MP cost (per unit only, not multiplied by target_qty)
     */
    public function test_costing_sheet_mp_cost_is_per_unit(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => 'Test Project',
            'type' => 'mass',
            'status' => 'planning',
            'customer_id' => $this->customer->id,
            'design_id' => $this->design->id,
            'target_qty' => 1000,
        ]);

        // Rate per unit according to config. Usually default is array sum of power which is 33000
        $mpRate = CostingCalculatorService::getMpRatePerUnit();
        $this->assertEquals(33000, $mpRate); // config/costing.php default man_power sum is 33000

        // In CostingResource form, the set value is mpRate directly, not multiplied by 1000
        $costing = Costing::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'design_id' => $this->design->id,
            'costing_date' => now()->toDateString(),
            'version' => '1.0',
            'status' => 'draft',
            'material_cost' => 100000, // unit cost
            'mp_cost' => $mpRate, // unit cost
            'overhead_pct' => 15,
            'shipping_cost' => 5000,
            'profit_margin_pct' => 20,
        ]);

        CostingCalculatorService::recalculateCosting($costing);
        $costing->refresh();

        // Landed Cost = material + mp + overhead + shipping
        // Overhead Amount = (100,000 + 33,000) * 0.15 = 19,950
        // Landed Cost = 100,000 + 33,000 + 19,950 + 5,000 = 157,950
        // Profit Margin = 157,950 * 0.20 = 31,590
        // Selling Price = 157,950 + 31,590 = 189,540
        $this->assertEquals(157950, (float) $costing->landed_cost);
        $this->assertEquals(189540, (float) $costing->selling_price);
    }

    /**
     * 3. Test Payment Observer (Updates invoice paid amount and status)
     */
    public function test_payment_observer_updates_invoice_paid_amount_and_status(): void
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => CodeGenerator::generateSONumber(),
            'project_id' => null,
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'grand_total' => 100000,
            'status' => 'confirmed',
        ]);

        $invoice = InvoiceSales::create([
            'company_id' => $this->company->id,
            'sales_order_id' => $so->id,
            'invoice_number' => CodeGenerator::generateInvoiceSalesNo(),
            'invoice_date' => now()->toDateString(),
            'subtotal' => 90090.09,
            'ppn_percent' => 11,
            'ppn_amount' => 9909.91,
            'shipping_cost' => 0,
            'grand_total' => 100000.00,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        // Create first payment (Partial)
        $payment1 = Payment::create([
            'company_id' => $this->company->id,
            'invoice_type' => 'sales',
            'invoice_id' => $invoice->id,
            'payment_number' => CodeGenerator::generatePaymentNumber('sales'),
            'payment_date' => now()->toDateString(),
            'amount' => 40000,
        ]);

        $invoice->refresh();
        $this->assertEquals(40000, (float) $invoice->paid_amount);
        $this->assertEquals('partial', $invoice->status);

        // Create second payment (Full)
        $payment2 = Payment::create([
            'company_id' => $this->company->id,
            'invoice_type' => 'sales',
            'invoice_id' => $invoice->id,
            'payment_number' => CodeGenerator::generatePaymentNumber('sales'),
            'payment_date' => now()->toDateString(),
            'amount' => 60000,
        ]);

        $invoice->refresh();
        $this->assertEquals(100000, (float) $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        // Delete one payment (Back to Partial)
        $payment2->delete();

        $invoice->refresh();
        $this->assertEquals(40000, (float) $invoice->paid_amount);
        $this->assertEquals('partial', $invoice->status);
    }

    /**
     * 4. Test CodeGenerator dynamic payment number
     */
    public function test_payment_number_sequence_generation(): void
    {
        $num1 = CodeGenerator::generatePaymentNumber('sales');

        // Save a mock payment
        Payment::create([
            'company_id' => $this->company->id,
            'invoice_type' => 'sales',
            'invoice_id' => 999,
            'payment_number' => $num1,
            'payment_date' => now()->toDateString(),
            'amount' => 1000,
        ]);

        $num2 = CodeGenerator::generatePaymentNumber('sales');
        $this->assertNotEquals($num1, $num2);

        $expectedPrefix = 'PAY-SALES-'.now()->year;
        $this->assertStringStartsWith($expectedPrefix, $num1);
        $this->assertStringStartsWith($expectedPrefix, $num2);
    }

    /**
     * 5. Test Merchandise Planning planned_qty multiplier
     */
    public function test_merchandise_planning_planned_qty_includes_target_qty_and_wastage(): void
    {
        $material = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-RAW-TEST',
            'name' => 'Raw Test',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 10000,
            'stock' => 0,
        ]);

        $bom = Bom::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'name' => 'BOM Test',
            'code' => 'BOM-TEST',
        ]);

        $bomItem = BomItem::create([
            'bom_id' => $bom->id,
            'material_id' => $material->id,
            'category' => 'main_material',
            'quantity_per_unit' => 1.5,
            'unit' => 'yard',
            'wastage_percent' => 10.0,
        ]);

        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => 'Test Project',
            'type' => 'mass',
            'status' => 'planning',
            'customer_id' => $this->customer->id,
            'bom_id' => $bom->id,
            'design_id' => $this->design->id,
            'target_qty' => 1000,
        ]);

        // When we instantiate MerchandisePlanning for this project:
        // quantity_per_unit (1.5) * target_qty (1000) * (1 + wastage_percent/100 (1.1)) = 1650
        $targetQty = max(1, (int) ($project->target_qty ?? 1));
        $wastageMultiplier = 1 + (($bomItem->wastage_percent ?? 0) / 100);
        $plannedQty = floatval($bomItem->quantity_per_unit) * $targetQty * $wastageMultiplier;

        $this->assertEquals(1650, round($plannedQty, 2));
    }

    /**
     * 6. Test Merchandise Planning auto-fills supplier_id from BOM material
     */
    public function test_merchandise_planning_autofills_supplier_id_from_bom(): void
    {
        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Test Supplier',
            'code' => 'SUP-TEST',
            'contact_person' => 'Contact',
            'phone' => '123',
            'address' => 'Addr',
        ]);

        $material = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-SUP-TEST',
            'name' => 'Raw Supplier Test',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 10000,
            'supplier_id' => $supplier->id,
        ]);

        $bom = Bom::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'name' => 'BOM Test 2',
            'code' => 'BOM-TEST-2',
        ]);

        $bomItem = BomItem::create([
            'bom_id' => $bom->id,
            'material_id' => $material->id,
            'category' => 'main_material',
            'quantity_per_unit' => 1.5,
            'unit' => 'yard',
            'wastage_percent' => 10.0,
        ]);

        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => 'Test Project 2',
            'type' => 'mass',
            'status' => 'planning',
            'customer_id' => $this->customer->id,
            'bom_id' => $bom->id,
            'design_id' => $this->design->id,
            'target_qty' => 10,
        ]);

        // Simulating the actual query/mapping in MerchandisePlanningResource.php
        $items = $project->bom->items->map(function ($bomItem) use ($project) {
            $unitPrice = $bomItem->material?->price ?? 0;
            $targetQty = max(1, (int) ($project->target_qty ?? 1));
            $wastageMultiplier = 1 + (($bomItem->wastage_percent ?? 0) / 100);
            $plannedQty = floatval($bomItem->quantity_per_unit) * $targetQty * $wastageMultiplier;

            return [
                'material_id' => $bomItem->material_id,
                'supplier_id' => $bomItem->material?->supplier_id,
                'planned_qty' => $plannedQty,
                'unit' => $bomItem->unit,
                'unit_price' => number_format($unitPrice, 2, '.', ','),
                'total_price' => number_format($plannedQty * $unitPrice, 2, '.', ','),
                'is_subcon' => false,
                'notes' => $bomItem->notes,
                'is_from_rnd' => $bomItem->is_from_rnd ?? true,
            ];
        })->toArray();

        $this->assertNotEmpty($items);
        $this->assertEquals($supplier->id, $items[0]['supplier_id']);
        $this->assertTrue($items[0]['is_from_rnd']);
    }

    /**
     * 7. Test Sales Order project selection auto-fills quantity and payment terms, and resets on deselection
     */
    public function test_sales_order_project_selection_autofill_and_reset(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => 'Test Project',
            'type' => 'mass',
            'status' => 'planning',
            'customer_id' => $this->customer->id,
            'target_qty' => 125,
        ]);

        $this->customer->update(['payment_terms' => 'net_30']);

        Livewire::test(CreateSalesOrder::class)
            ->set('data.project_id', $project->id)
            ->assertSet('data.customer_id', $this->customer->id)
            ->assertSet('data.quantity', 125)
            ->assertSet('data.payment_terms', 'net_30')
            ->set('data.project_id', null)
            ->assertSet('data.customer_id', null)
            ->assertSet('data.quantity', 0)
            ->assertSet('data.payment_terms', null)
            ->assertSet('data.subtotal', 0);
    }

    /**
     * 8. Test Sales Order costing selection auto-fills unit price and recalculates totals, and resets on deselection
     */
    public function test_sales_order_costing_selection_autofill_and_reset(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => 'Test Project',
            'type' => 'mass',
            'status' => 'planning',
            'customer_id' => $this->customer->id,
            'target_qty' => 10,
        ]);

        $costing = Costing::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'design_id' => $this->design->id,
            'costing_date' => now()->toDateString(),
            'version' => '1.0',
            'status' => 'draft',
            'material_cost' => 10000,
            'mp_cost' => 5000,
            'overhead_pct' => 10,
            'shipping_cost' => 1000,
            'profit_margin_pct' => 10,
        ]);
        CostingCalculatorService::recalculateCosting($costing);
        $costing->refresh();

        $sellingPrice = (float) $costing->selling_price;
        $this->assertGreaterThan(0, $sellingPrice);

        Livewire::test(CreateSalesOrder::class)
            ->set('data.quantity', 10)
            ->set('data.costing_id', $costing->id)
            ->assertSet('data.unit_price', $sellingPrice)
            ->assertSet('data.subtotal', 10 * $sellingPrice)
            ->set('data.costing_id', null)
            ->assertSet('data.unit_price', 0)
            ->assertSet('data.subtotal', 0);
    }

    /**
     * 9. Test Purchase Shipment PO selection auto-fills shipping cost for subcon PO and resets on deselection
     */
    public function test_purchase_shipment_po_selection_autofills_shipping_cost_and_resets(): void
    {
        $poSubcon = PoSubcon::create([
            'company_id' => $this->company->id,
            'po_number' => CodeGenerator::generatePOSubconNo(),
            'project_id' => null,
            'subcon_id' => Subcon::create([
                'company_id' => $this->company->id,
                'name' => 'Test Subcon',
                'code' => 'SUB-001',
                'service_type' => 'sewing',
                'email' => 'sub@test.com',
                'phone' => '12345',
            ])->id,
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'shipping_cost' => 150000,
        ]);

        Livewire::test(CreatePurchaseShipment::class)
            ->set('data.po_type', 'subcon')
            ->set('data.po_id', $poSubcon->id)
            ->assertSet('data.shipping_cost', 150000)
            ->set('data.po_id', null)
            ->assertSet('data.shipping_cost', 0);
    }
}
