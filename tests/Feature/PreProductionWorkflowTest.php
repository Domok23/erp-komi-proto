<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Subcon;
use App\Models\Material;
use App\Models\Warehouse;
use App\Models\RdDesign;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Project;
use App\Models\Costing;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\PoSupplier;
use App\Models\PoSubcon;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryStock;
use App\Models\InventoryMovement;
use App\Models\SalesOrder;
use App\Models\InvoiceSales;
use App\Models\InvoicePurchase;
use App\Models\Payment;
use App\Services\ProjectTransitionService;
use App\Services\InventoryService;
use App\Services\InvoiceGeneratorService;
use App\Services\CostingCalculatorService;
use App\Services\CodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PreProductionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_pre_production_workflow(): void
    {
        // 1. Setup Base Data
        $user = User::factory()->create();
        
        $company = Company::create([
            'name' => 'PT Komitrando Emporio Test',
            'code' => 'KOMI-TEST',
            'address' => 'Jogja',
        ]);

        $customer = Customer::create([
            'company_id' => $company->id,
            'code' => 'CUST-001',
            'name' => 'Customer A',
            'email' => 'customer.a@gmail.com',
            'phone' => '1234567890',
            'address' => 'Singapore',
        ]);

        $supplier = Supplier::create([
            'company_id' => $company->id,
            'name' => 'Supplier Textile Corp',
            'code' => 'SUP-TEX',
            'contact_person' => 'John Textile',
            'email' => 'john@textile.com',
            'phone' => '1111111',
            'address' => 'Bandung',
        ]);

        $subcon = Subcon::create([
            'company_id' => $company->id,
            'name' => 'Sewing Subcon Jogja',
            'code' => 'SUB-SEW',
            'service_type' => 'sewing',
            'contact_person' => 'Slamet Sewing',
            'email' => 'slamet@sewing.com',
            'phone' => '2222222',
            'address' => 'Sleman',
        ]);

        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-NYLON-01',
            'name' => 'Nylon Fabric Blue',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 10000,
            'stock' => 0,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $company->id,
            'code' => 'WH-MAIN',
            'name' => 'Main Warehouse',
            'address' => 'Bantul',
            'is_active' => true,
        ]);

        // 2. R&D: Create Design -> BOM -> BOM Item
        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DES-BAG-001',
            'name' => 'Classic Backpack Test',
            'bag_type' => 'backpack',
            'status' => 'approved',
        ]);

        $bom = Bom::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'name' => 'BOM Version 1',
            'version' => '1.0',
            'status' => 'active',
        ]);

        $bomItem = BomItem::create([
            'bom_id' => $bom->id,
            'material_id' => $material->id,
            'category' => 'main_material',
            'quantity_per_unit' => 2.5,
            'unit' => 'yard',
            'wastage_percent' => 10,
        ]);

        $this->assertDatabaseHas('boms', ['id' => $bom->id]);
        $this->assertDatabaseHas('bom_items', ['id' => $bomItem->id]);

        // 3. Project Initiation: Proto -> Approve -> Sample Auto-Created
        $projectProto = Project::create([
            'company_id' => $company->id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => 'Classic Backpack Proto Project',
            'type' => 'proto',
            'status' => 'planning',
            'customer_id' => $customer->id,
            'design_id' => $design->id,
            'bom_id' => $bom->id,
            'target_qty' => 1,
        ]);

        $this->assertNotNull($projectProto->project_code);
        $this->assertStringStartsWith('PRJ-', $projectProto->project_code);

        // Approve Proto
        $projectSample = ProjectTransitionService::approveProject($projectProto, $user->id);

        $projectProto->refresh();
        $this->assertEquals('approved', $projectProto->status);
        $this->assertNotNull($projectProto->approved_at);
        $this->assertEquals($user->id, $projectProto->approved_by);

        // Check Sample Created
        $this->assertNotNull($projectSample);
        $this->assertEquals('sample', $projectSample->type);
        $this->assertEquals('planning', $projectSample->status);
        $this->assertEquals($projectProto->id, $projectSample->reference_project_id);

        // 4. Costing / Pricing: Calculate from BOM
        $costing = Costing::create([
            'company_id' => $company->id,
            'project_id' => $projectSample->id,
            'costing_code' => 'CST-001',
            'costing_date' => now()->toDateString(),
            'material_cost' => 0,
            'mp_cost' => 50000,
            'overhead_pct' => 10,
            'profit_margin_pct' => 20,
            'shipping_cost' => 15000,
            'status' => 'draft',
        ]);

        // Calculate material cost from BOM
        $bomCosts = CostingCalculatorService::calculateFromBOM($projectSample);
        // quantity_per_unit = 2.5, wastage = 10% -> quantity with wastage = 2.75. Material price = 10000 -> 27500.
        $this->assertEquals(27500, $bomCosts['material_cost']);

        $costing->update(['material_cost' => $bomCosts['material_cost']]);
        CostingCalculatorService::recalculateCosting($costing);

        $costing->refresh();
        // material_cost (27500) + mp_cost (50000) = 77500
        // overhead (10%) = 7750
        // landed_cost = 77500 + 7750 + shipping (15000) = 100250
        // profit (20%) = 20050
        // selling_price = 120300
        $this->assertEquals(7750, $costing->overhead_amount);
        $this->assertEquals(100250, $costing->landed_cost);
        $this->assertEquals(20050, $costing->profit_margin_amount);
        $this->assertEquals(120300, $costing->selling_price);

        // 5. Sales Order
        $so = SalesOrder::create([
            'company_id' => $company->id,
            'project_id' => $projectSample->id,
            'costing_id' => $costing->id,
            'so_number' => CodeGenerator::generateSONumber(),
            'order_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'quantity' => 100,
            'unit_price' => $costing->selling_price,
            'subtotal' => 100 * $costing->selling_price,
            'ppn_percent' => 11,
            'ppn_amount' => (100 * $costing->selling_price) * 0.11,
            'shipping_cost' => 100000,
            'grand_total' => (100 * $costing->selling_price) * 1.11 + 100000,
            'status' => 'draft',
        ]);

        $this->assertStringStartsWith('SO-', $so->so_number);

        // 6. Merchandise Planning -> Generate POs
        $planning = MerchandisePlanning::create([
            'company_id' => $company->id,
            'project_id' => $projectSample->id,
            'design_id' => $design->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 275000,
            'total_subcon_cost' => 500000,
        ]);

        // Add planning item for Supplier
        $planItemSup = MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'material_id' => $material->id,
            'supplier_id' => $supplier->id,
            'planned_qty' => 275,
            'unit' => 'yard',
            'unit_price' => 1000,
            'total_price' => 275000,
            'is_subcon' => false,
        ]);

        // Add planning item for Subcon
        $planItemSub = MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'subcon_id' => $subcon->id,
            'planned_qty' => 100,
            'unit' => 'pcs',
            'unit_price' => 5000,
            'total_price' => 500000,
            'is_subcon' => true,
        ]);

        // Execute "Generate POs" action logic manually
        // Group items by supplier for supplier POs
        $supplierItems = $planning->items->where('is_subcon', false)->groupBy('supplier_id');
        foreach ($supplierItems as $supplierId => $items) {
            $po = PoSupplier::create([
                'company_id' => $planning->company_id,
                'po_number' => CodeGenerator::generatePOSupplierNo(),
                'project_id' => $planning->project_id,
                'supplier_id' => $supplierId,
                'po_date' => now()->toDateString(),
                'status' => 'draft',
            ]);
            
            $subtotal = 0;
            foreach ($items as $item) {
                \App\Models\PoSupplierItem::create([
                    'po_supplier_id' => $po->id,
                    'material_id' => $item->material_id,
                    'description' => 'Blue Fabric',
                    'qty' => $item->planned_qty,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'qty_received' => 0,
                ]);
                $subtotal += $item->total_price;
            }
            
            $ppn = $subtotal * 0.11;
            $po->update([
                'subtotal' => $subtotal,
                'ppn_percent' => 11,
                'ppn_amount' => $ppn,
                'grand_total' => $subtotal + $ppn,
            ]);
        }

        // Verify PoSupplier was created
        $poSupplier = PoSupplier::first();
        $this->assertNotNull($poSupplier);
        $this->assertEquals(275000, $poSupplier->subtotal);
        $this->assertEquals(305250, $poSupplier->grand_total);
        $this->assertStringStartsWith('PO-SUP-', $poSupplier->po_number);

        // 7. Purchase Goods Receipt -> Update Inventory
        $goodsReceipt = GoodsReceipt::create([
            'company_id' => $company->id,
            'gr_number' => CodeGenerator::generateGRNumber(),
            'warehouse_id' => $warehouse->id,
            'po_type' => PoSupplier::class,
            'po_id' => $poSupplier->id,
            'receipt_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $grItem = \App\Models\GoodsReceiptItem::create([
            'goods_receipt_id' => $goodsReceipt->id,
            'material_id' => $material->id,
            'qty_ordered' => 275,
            'qty_received' => 275,
            'qty_rejected' => 0,
            'unit' => 'yard',
            'notes' => 'Good condition',
        ]);

        // Trigger Inventory update by updating status to verified
        $goodsReceipt->update(['status' => 'verified']);

        // Verify Stock updated
        $stock = InventoryStock::where('warehouse_id', $warehouse->id)->where('material_id', $material->id)->first();
        $this->assertNotNull($stock);
        $this->assertEquals(275, $stock->quantity);
        $this->assertEquals(275, $stock->available_qty);

        // Verify movement created
        $movement = InventoryMovement::where('inventory_stock_id', $stock->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals(275, $movement->quantity);
        $this->assertEquals('purchase', $movement->type);

        // Verify Material global stock synced
        $material->refresh();
        $this->assertEquals(275, $material->stock);

        // 8. Invoice Purchase & Sales & Payments
        $invSales = InvoiceGeneratorService::generateFromSO($so);
        $this->assertNotNull($invSales);
        $this->assertStringStartsWith('INV-SALES-', $invSales->invoice_number);
        $this->assertEquals($so->grand_total, $invSales->grand_total);

        $invPurchase = InvoiceGeneratorService::generateFromPO($poSupplier);
        $this->assertNotNull($invPurchase);
        $this->assertStringStartsWith('INV-PUR-', $invPurchase->invoice_number);
        $this->assertEquals($poSupplier->grand_total, $invPurchase->grand_total);

        // Record a Payment
        $payment = Payment::create([
            'company_id' => $company->id,
            'payment_number' => 'PAY-001',
            'payment_date' => now()->toDateString(),
            'invoice_type' => InvoicePurchase::class,
            'invoice_id' => $invPurchase->id,
            'amount' => 150000, // partial payment
            'payment_method' => 'bank_transfer',
            'notes' => 'Partial payment',
        ]);

        // Update invoice status based on payment (simulated inline or hook)
        $invPurchase->update([
            'paid_amount' => $invPurchase->paid_amount + $payment->amount,
            'status' => 'partial',
        ]);

        $invPurchase->refresh();
        $this->assertEquals(150000, $invPurchase->paid_amount);
        $this->assertEquals('partial', $invPurchase->status);
    }
}
