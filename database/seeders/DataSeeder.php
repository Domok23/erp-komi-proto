<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Subcon;
use App\Models\Supplier;
use App\Models\Material;
use App\Models\Warehouse;
use App\Models\RdDesign;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Project;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\Costing;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\PoSupplier;
use App\Models\PoSupplierItem;
use App\Models\PoSubcon;
use App\Models\PoSubconItem;
use App\Models\PurchaseTracking;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceiptShipping;
use App\Models\SubconMaterialOut;
use App\Models\SubconMaterialOutItem;
use App\Models\SubconMaterialIn;
use App\Models\SubconMaterialInItem;
use App\Models\InventoryStock;
use App\Models\InvoiceSales;
use App\Models\InvoicePurchase;
use App\Models\Payment;
use App\Services\CodeGenerator;
use Illuminate\Database\Seeder;

class DataSeeder extends Seeder
{
    public function run(): void
    {
        $kei = Company::where('code', 'KEI')->first();
        $ktk = Company::where('code', 'KTK')->first();

        // 1. Seed Warehouses
        $whMain = Warehouse::create([
            'company_id' => $kei->id,
            'code' => 'WH-MAIN',
            'name' => 'Main Warehouse',
            'address' => 'Bandung Main Office',
            'is_active' => true,
        ]);

        $whBranch = Warehouse::create([
            'company_id' => $kei->id,
            'code' => 'WH-BRANCH',
            'name' => 'Branch Warehouse',
            'address' => 'Bandung Branch Office',
            'is_active' => true,
        ]);

        // 2. Seed Suppliers
        $supplierYKK = Supplier::create([
            'company_id' => $kei->id,
            'code' => 'SUP-001',
            'name' => 'PT YKK Indonesia',
            'contact_person' => 'Budi Santoso',
            'address' => 'Jakarta Timur',
            'is_active' => true,
        ]);

        $supplierDuraflex = Supplier::create([
            'company_id' => $kei->id,
            'code' => 'SUP-002',
            'name' => 'PT Duraflex Belting',
            'contact_person' => 'Siti Nurhaliza',
            'address' => 'Bandung',
            'is_active' => true,
        ]);

        // 3. Seed Subcons
        $subconJaya = Subcon::create([
            'company_id' => $kei->id,
            'code' => 'SUB-001',
            'name' => 'CV Jaya Bordir',
            'service_type' => 'embroidery',
            'contact_person' => 'Tono Rahardjo',
            'address' => 'Jakarta Barat',
            'is_active' => true,
        ]);

        // 4. Seed Customers
        $customerVera = Customer::create([
            'company_id' => $kei->id,
            'code' => 'CUS-001',
            'name' => 'Vera Bradley Exports, Inc.',
            'contact_person' => 'Sarah Mitchell',
            'address' => 'United States',
            'payment_terms' => 'net_60',
            'is_active' => true,
        ]);

        // 5. Seed Materials
        $matFabric = Material::create([
            'code' => 'FAB-001',
            'name' => '600D Recycled Polyester Dobby',
            'category' => 'fabric',
            'unit' => 'yard',
            'stock' => 1000,
            'min_stock' => 100,
            'price' => 38000,
            'supplier_id' => $supplierYKK->id,
        ]);

        $matZipper = Material::create([
            'code' => 'ZIP-001',
            'name' => 'YKK #5 Metal Zipper, Nickel',
            'category' => 'zipper',
            'unit' => 'pcs',
            'stock' => 5000,
            'min_stock' => 500,
            'price' => 7500,
            'supplier_id' => $supplierYKK->id,
        ]);

        $matWebbing = Material::create([
            'code' => 'ACC-003',
            'name' => 'Webbing Tape 38mm Nylon, Black',
            'category' => 'other',
            'unit' => 'meter',
            'stock' => 2000,
            'min_stock' => 200,
            'price' => 2500,
            'supplier_id' => $supplierDuraflex->id,
        ]);

        // 6. Seed RdDesigns
        $designBackpack = RdDesign::create([
            'company_id' => $kei->id,
            'code' => 'DSN-EBP-001',
            'name' => 'Explorer Backpack Pro',
            'description' => 'High-performance backpack design',
            'bag_type' => 'backpack',
            'status' => 'approved',
            'brand' => 'Explorer',
            'size_range' => '30L',
            'notes' => 'Initial approved R&D model',
        ]);

        // 7. Seed BOMs and BOM Items
        $bomBackpack = Bom::create([
            'company_id' => $kei->id,
            'design_id' => $designBackpack->id,
            'name' => 'Main BOM Explorer Backpack',
            'version' => '1.0',
            'status' => 'active',
        ]);

        BomItem::create([
            'bom_id' => $bomBackpack->id,
            'material_id' => $matFabric->id,
            'category' => 'main_material',
            'quantity_per_unit' => 1.5,
            'unit' => 'yard',
            'wastage_percent' => 5,
        ]);

        BomItem::create([
            'bom_id' => $bomBackpack->id,
            'material_id' => $matZipper->id,
            'category' => 'hardware',
            'quantity_per_unit' => 3,
            'unit' => 'pcs',
            'wastage_percent' => 2,
        ]);

        BomItem::create([
            'bom_id' => $bomBackpack->id,
            'material_id' => $matWebbing->id,
            'category' => 'trim',
            'quantity_per_unit' => 2.5,
            'unit' => 'meter',
            'wastage_percent' => 0,
        ]);

        // 8. Seed Projects
        $project = Project::create([
            'company_id' => $kei->id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => 'Vera Bradley Q3 Backpack',
            'description' => 'Mass production order for 1,000 units',
            'type' => 'mass',
            'status' => 'production',
            'customer_id' => $customerVera->id,
            'design_id' => $designBackpack->id,
            'bom_id' => $bomBackpack->id,
            'target_qty' => 1000,
        ]);

        // 9. Seed Merchandise Plannings
        $planning = MerchandisePlanning::create([
            'company_id' => $kei->id,
            'project_id' => $project->id,
            'design_id' => $designBackpack->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 120000,
            'total_subcon_cost' => 50000,
            'special_instructions' => 'Embroidery to be done by CV Jaya Bordir',
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'material_id' => $matFabric->id,
            'supplier_id' => $supplierYKK->id,
            'planned_qty' => 1500,
            'unit' => 'yard',
            'unit_price' => 38000,
            'total_price' => 57000000,
            'is_subcon' => false,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'material_id' => $matZipper->id,
            'supplier_id' => $supplierYKK->id,
            'planned_qty' => 3000,
            'unit' => 'pcs',
            'unit_price' => 7500,
            'total_price' => 22500000,
            'is_subcon' => false,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'subcon_id' => $subconJaya->id,
            'planned_qty' => 1000,
            'unit' => 'pcs',
            'unit_price' => 15000,
            'total_price' => 15000000,
            'is_subcon' => true,
        ]);

        // 10. Seed Costings
        $costing = Costing::create([
            'company_id' => $kei->id,
            'project_id' => $project->id,
            'design_id' => $designBackpack->id,
            'costing_date' => now()->toDateString(),
            'version' => '1.0',
            'status' => 'approved',
            'material_cost' => 90000,
            'mp_cost' => 20000,
            'overhead_pct' => 15,
            'shipping_cost' => 5000,
            'profit_margin_pct' => 20,
            'currency' => 'IDR',
        ]);
        \App\Services\CostingCalculatorService::recalculateCosting($costing);

        // 11. Seed Sales Orders
        $salesOrder = SalesOrder::create([
            'company_id' => $kei->id,
            'so_number' => CodeGenerator::generateSONumber(),
            'project_id' => $project->id,
            'costing_id' => $costing->id,
            'customer_id' => $customerVera->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(60)->toDateString(),
            'quantity' => 1000,
            'unit_price' => $costing->selling_price,
            'status' => 'confirmed',
            'currency' => 'IDR',
            'exchange_rate' => 1,
            'subtotal' => 1000 * $costing->selling_price,
            'ppn_percent' => 11,
            'ppn_amount' => (1000 * $costing->selling_price) * 0.11,
            'shipping_cost' => 2000000,
            'grand_total' => (1000 * $costing->selling_price) * 1.11 + 2000000,
            'down_payment_pct' => 30,
            'down_payment_amount' => ((1000 * $costing->selling_price) * 1.11 + 2000000) * 0.30,
            'payment_terms' => 'dp_30',
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'description' => 'Explorer Backpack Pro 30L',
            'quantity' => 1000,
            'unit' => 'pcs',
            'unit_price' => $costing->selling_price,
            'total_price' => 1000 * $costing->selling_price,
        ]);

        // Update Project Sales Order link
        $project->update(['sales_order_id' => $salesOrder->id]);

        // 12. Seed PO Suppliers
        $poSupplier = PoSupplier::create([
            'company_id' => $kei->id,
            'po_number' => CodeGenerator::generatePOSupplierNo(),
            'project_id' => $project->id,
            'supplier_id' => $supplierYKK->id,
            'po_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(20)->toDateString(),
            'status' => 'ordered',
            'subtotal' => 79500000,
            'ppn_percent' => 11,
            'ppn_amount' => 79500000 * 0.11,
            'grand_total' => 79500000 * 1.11,
            'notes' => 'Zippers and fabric for Backpack production',
        ]);

        PoSupplierItem::create([
            'po_supplier_id' => $poSupplier->id,
            'material_id' => $matFabric->id,
            'description' => '600D Recycled Polyester Dobby',
            'qty' => 1500,
            'unit' => 'yard',
            'unit_price' => 38000,
            'total_price' => 57000000,
            'qty_received' => 0,
        ]);

        PoSupplierItem::create([
            'po_supplier_id' => $poSupplier->id,
            'material_id' => $matZipper->id,
            'description' => 'YKK #5 Metal Zipper',
            'qty' => 3000,
            'unit' => 'pcs',
            'unit_price' => 7500,
            'total_price' => 22500000,
            'qty_received' => 0,
        ]);

        // 13. Seed PO Subcons
        $poSubcon = PoSubcon::create([
            'company_id' => $kei->id,
            'po_number' => CodeGenerator::generatePOSubconNo(),
            'project_id' => $project->id,
            'subcon_id' => $subconJaya->id,
            'po_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(25)->toDateString(),
            'status' => 'ordered',
            'service_cost' => 15000000,
            'shipping_cost' => 500000,
            'shipping_return_cost' => 500000,
            'total_cost' => 16000000,
        ]);

        PoSubconItem::create([
            'po_subcon_id' => $poSubcon->id,
            'description' => 'Logo Embroidery Service',
            'qty' => 1000,
            'unit_price' => 15000,
            'total_price' => 15000000,
        ]);

        // 14. Seed Purchase Tracking
        PurchaseTracking::create([
            'company_id' => $kei->id,
            'po_type' => 'supplier',
            'po_id' => $poSupplier->id,
            'tracking_status' => 'shipped',
            'estimated_arrival' => now()->addDays(5)->toDateString(),
            'notes' => 'On transit from Jakarta port',
        ]);

        // 15. Seed Goods Receipt (In Draft)
        $goodsReceipt = GoodsReceipt::create([
            'company_id' => $kei->id,
            'gr_number' => CodeGenerator::generateGRNumber(),
            'po_type' => 'supplier',
            'po_id' => $poSupplier->id,
            'warehouse_id' => $whMain->id,
            'receipt_date' => now()->toDateString(),
            'status' => 'draft',
            'received_by' => 'Joko',
            'notes' => 'Arrived partial batch 1',
        ]);

        GoodsReceiptItem::create([
            'goods_receipt_id' => $goodsReceipt->id,
            'material_id' => $matFabric->id,
            'qty_ordered' => 1500,
            'qty_received' => 1500,
            'qty_rejected' => 0,
            'unit' => 'yard',
        ]);

        GoodsReceiptItem::create([
            'goods_receipt_id' => $goodsReceipt->id,
            'material_id' => $matZipper->id,
            'qty_ordered' => 3000,
            'qty_received' => 3000,
            'qty_rejected' => 0,
            'unit' => 'pcs',
        ]);

        GoodsReceiptShipping::create([
            'goods_receipt_id' => $goodsReceipt->id,
            'carrier' => 'JNE Cargo',
            'tracking_number' => 'JNE-12345678',
            'shipping_cost' => 120000,
            'received_condition' => 'good',
        ]);

        // 16. Seed Subcon Material OUT
        $subconOut = SubconMaterialOut::create([
            'company_id' => $kei->id,
            'po_subcon_id' => $poSubcon->id,
            'subcon_id' => $subconJaya->id,
            'document_number' => 'MAT-OUT-001',
            'departure_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        SubconMaterialOutItem::create([
            'subcon_material_out_id' => $subconOut->id,
            'material_id' => $matFabric->id,
            'qty_sent' => 200,
            'unit' => 'yard',
        ]);

        // 17. Seed Subcon Material IN
        $subconIn = SubconMaterialIn::create([
            'company_id' => $kei->id,
            'po_subcon_id' => $poSubcon->id,
            'subcon_id' => $subconJaya->id,
            'document_number' => 'MAT-IN-001',
            'receive_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        SubconMaterialInItem::create([
            'subcon_material_in_id' => $subconIn->id,
            'material_id' => $matFabric->id,
            'qty_received' => 200,
            'qty_rejected' => 0,
            'unit' => 'yard',
        ]);

        // 18. Seed Invoices & Payments
        $invoiceSales = InvoiceSales::create([
            'company_id' => $kei->id,
            'sales_order_id' => $salesOrder->id,
            'invoice_number' => CodeGenerator::generateInvoiceSalesNo(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => $salesOrder->subtotal,
            'ppn_percent' => 11,
            'ppn_amount' => $salesOrder->ppn_amount,
            'shipping_cost' => $salesOrder->shipping_cost,
            'grand_total' => $salesOrder->grand_total,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'is_tax_invoice' => true,
            'tax_invoice_number' => '010.000-26.00000001',
        ]);

        // 19. Seed Inventory Stock details
        InventoryStock::create([
            'company_id' => $kei->id,
            'warehouse_id' => $whMain->id,
            'material_id' => $matFabric->id,
            'quantity' => 1000,
            'reserved_qty' => 0,
            'available_qty' => 1000,
            'unit' => 'yard',
            'min_stock' => 100,
            'location' => 'Aisle A-1',
        ]);

        InventoryStock::create([
            'company_id' => $kei->id,
            'warehouse_id' => $whMain->id,
            'material_id' => $matZipper->id,
            'quantity' => 5000,
            'reserved_qty' => 0,
            'available_qty' => 5000,
            'unit' => 'pcs',
            'min_stock' => 500,
            'location' => 'Bin B-12',
        ]);

        InventoryStock::create([
            'company_id' => $kei->id,
            'warehouse_id' => $whMain->id,
            'material_id' => $matWebbing->id,
            'quantity' => 2000,
            'reserved_qty' => 0,
            'available_qty' => 2000,
            'unit' => 'meter',
            'min_stock' => 200,
            'location' => 'Rack C-3',
        ]);
    }
}