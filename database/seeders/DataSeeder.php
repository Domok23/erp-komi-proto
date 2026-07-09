<?php

namespace Database\Seeders;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Company;
use App\Models\Costing;
use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceiptShipping;
use App\Models\InventoryStock;
use App\Models\InvoiceSales;
use App\Models\JobOrder;
use App\Models\JobOrderMaterial;
use App\Models\Material;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\PoSubcon;
use App\Models\PoSubconItem;
use App\Models\PoSupplier;
use App\Models\PoSupplierItem;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\Project;
use App\Models\PurchaseShipment;
use App\Models\QcInspection;
use App\Models\RdDesign;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Subcon;
use App\Models\SubconMaterialIn;
use App\Models\SubconMaterialInItem;
use App\Models\SubconMaterialOut;
use App\Models\SubconMaterialOutItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CodeGenerator;
use App\Services\CostingCalculatorService;
use Illuminate\Database\Seeder;

class DataSeeder extends Seeder
{
    public function run(): void
    {
        // Create companies if they don't exist
        $kei = Company::where('code', 'KEI')->first();
        if (! $kei) {
            $kei = Company::create([
                'code' => 'KEI',
                'name' => 'Karya Eka Indonesia',
                'address' => 'Bandung, Indonesia',
                'phone' => '+62 22 1234567',
                'email' => 'info@kei.co.id',
                'is_active' => true,
            ]);
        }

        $ktk = Company::where('code', 'KTK')->first();
        if (! $ktk) {
            $ktk = Company::create([
                'code' => 'KTK',
                'name' => 'Karya Teknik Kencana',
                'address' => 'Jakarta, Indonesia',
                'phone' => '+62 21 7654321',
                'email' => 'info@ktk.co.id',
                'is_active' => true,
            ]);
        }

        // 1. Seed Warehouses
        $whMain = Warehouse::where('code', 'WH-MAIN')->where('company_id', $kei->id)->first();
        if (!$whMain) {
            $whMain = Warehouse::create([
                'company_id' => $kei->id,
                'code' => 'WH-MAIN',
                'name' => 'Main Warehouse',
                'address' => 'Bandung Main Office',
                'is_active' => true,
            ]);
        }

        $whBranch = Warehouse::where('code', 'WH-BRANCH')->where('company_id', $kei->id)->first();
        if (!$whBranch) {
            $whBranch = Warehouse::create([
                'company_id' => $kei->id,
                'code' => 'WH-BRANCH',
                'name' => 'Branch Warehouse',
                'address' => 'Bandung Branch Office',
                'is_active' => true,
            ]);
        }

        $whMainKtk = Warehouse::create([
            'company_id' => $ktk->id,
            'code' => 'WH-MAIN-KTK',
            'name' => 'Main Warehouse KTK',
            'address' => 'Jakarta Main Office',
            'is_active' => true,
        ]);

        $whBranchKtk = Warehouse::create([
            'company_id' => $ktk->id,
            'code' => 'WH-BRANCH-KTK',
            'name' => 'Branch Warehouse KTK',
            'address' => 'Jakarta Branch Office',
            'is_active' => true,
        ]);

        // 2. Seed Suppliers
        $supplierYKK = Supplier::where('code', 'SUP-001')->where('company_id', $kei->id)->first();
        if (!$supplierYKK) {
            $supplierYKK = Supplier::create([
                'company_id' => $kei->id,
                'code' => 'SUP-001',
                'name' => 'PT Aksesoris Utama',
                'contact_person' => fake()->name(),
                'address' => fake()->address(),
                'is_active' => true,
            ]);
        }

        $supplierDuraflex = Supplier::where('code', 'SUP-002')->where('company_id', $kei->id)->first();
        if (!$supplierDuraflex) {
            $supplierDuraflex = Supplier::create([
                'company_id' => $kei->id,
                'code' => 'SUP-002',
                'name' => 'PT Supplier Inc',
                'contact_person' => fake()->name(),
                'address' => fake()->address(),
                'is_active' => true,
            ]);
        }

        // 3. Seed Subcons
        $subconJaya = Subcon::where('code', 'SUB-001')->where('company_id', $kei->id)->first();
        if (!$subconJaya) {
            $subconJaya = Subcon::create([
                'company_id' => $kei->id,
                'code' => 'SUB-001',
                'name' => 'CV Bordir Indonesia',
                'service_type' => 'embroidery',
                'contact_person' => fake()->name(),
                'address' => fake()->address(),
                'is_active' => true,
            ]);
        }

        // 4. Seed Customers
        $customerVera = Customer::where('code', 'CUS-001')->where('company_id', $kei->id)->first();
        if (!$customerVera) {
            $customerVera = Customer::create([
                'company_id' => $kei->id,
                'code' => 'CUS-001',
                'name' => 'PT Nike inc',
                'contact_person' => fake()->name(),
                'address' => fake()->address(),
                'payment_terms' => 'net_60',
                'is_active' => true,
            ]);
        }

        // 5. Seed Materials
        $matFabric = Material::where('code', 'FAB-001')->first();
        if (!$matFabric) {
            $matFabric = Material::create([
                'code' => 'FAB-001',
                'name' => 'Kain Polyester Hitam',
                'category' => 'fabric',
                'unit' => 'yard',
                'stock' => 1000,
                'min_stock' => 100,
                'price' => 38000,
                'supplier_id' => $supplierYKK->id,
            ]);
        }

        $matZipper = Material::where('code', 'ZIP-001')->first();
        if (!$matZipper) {
            $matZipper = Material::create([
                'code' => 'ZIP-001',
                'name' => 'Metal Zipper',
                'category' => 'zipper',
                'unit' => 'pcs',
                'stock' => 5000,
                'min_stock' => 500,
                'price' => 7500,
                'supplier_id' => $supplierYKK->id,
            ]);
        }

        $matWebbing = Material::where('code', 'ACC-003')->first();
        if (!$matWebbing) {
            $matWebbing = Material::create([
                'code' => 'ACC-003',
                'name' => 'Kancing Premium',
                'category' => 'other',
                'unit' => 'pcs',
                'stock' => 2000,
                'min_stock' => 200,
                'price' => 2500,
                'supplier_id' => $supplierDuraflex->id,
            ]);
        }

        $matFabricEmbroidered = Material::where('code', 'FAB-001-EMB')->first();
        if (!$matFabricEmbroidered) {
            $matFabricEmbroidered = Material::create([
                'code' => 'FAB-001-EMB',
                'name' => 'Kain Polyester Merah (Embroidered)',
                'category' => 'semi_finished',
                'unit' => 'pcs',
                'stock' => 0,
                'min_stock' => 0,
                'price' => 45000,
                'description' => 'Fabric after embroidery processing at subcontractor',
            ]);
        }

        // 6. Seed RdDesigns
        $designBackpack = RdDesign::create([
            'company_id' => $kei->id,
            'code' => 'DSN-EBP-001',
            'name' => 'Safari Jacket Pro',
            'description' => 'High-performance safari jacket design',
            'product_type' => 'jacket',
            'status' => 'approved',
            'brand' => 'Safari',
            'size_range' => 'M-XXL',
            'notes' => 'Initial approved R&D model',
        ]);

        // 7. Seed BOMs and BOM Items
        $bomBackpack = Bom::create([
            'company_id' => $kei->id,
            'bom_number' => CodeGenerator::generateBOMNumber($designBackpack->id, '1.0'),
            'design_id' => $designBackpack->id,
            'name' => 'Main BOM Safari Jacket',
            'version' => '1.0',
            'status' => 'active',
        ]);

        BomItem::create([
            'bom_id' => $bomBackpack->id,
            'material_id' => $matFabric->id,
            'category' => 'main_material',
            'quantity_per_unit' => 1.5,
            'unit' => 'kg',
            'wastage_percent' => 5,
            'is_from_rnd' => true,
        ]);

        BomItem::create([
            'bom_id' => $bomBackpack->id,
            'material_id' => $matZipper->id,
            'category' => 'components',
            'quantity_per_unit' => 3,
            'unit' => 'pcs',
            'wastage_percent' => 2,
            'is_from_rnd' => true,
        ]);

        BomItem::create([
            'bom_id' => $bomBackpack->id,
            'material_id' => $matWebbing->id,
            'category' => 'trim',
            'quantity_per_unit' => 6,
            'unit' => 'pcs',
            'wastage_percent' => 0,
            'is_from_rnd' => true,
        ]);

        // 8. Seed Projects
        $project = Project::create([
            'company_id' => $kei->id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => 'Nike Jacket',
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
            'special_instructions' => 'Embroidery to be done by CV Bordir Indonesia',
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'material_id' => $matFabric->id,
            'supplier_id' => $supplierYKK->id,
            'planned_qty' => 1500,
            'unit' => 'kg',
            'unit_price' => 38000,
            'total_price' => 57000000,
            'is_subcon' => false,
            'is_from_rnd' => true,
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
            'is_from_rnd' => true,
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
        $admin = User::where('email', 'admin@komi.com')->first();
        $costing = Costing::create([
            'company_id' => $kei->id,
            'project_id' => $project->id,
            'design_id' => $designBackpack->id,
            'costing_date' => now()->toDateString(),
            'version' => '1.0',
            'status' => 'draft',
            'material_cost' => 90000,
            'mp_cost' => 33000,
            'overhead_pct' => 15,
            'shipping_cost' => 5000,
            'profit_margin_pct' => 20,
            'currency' => 'IDR',
        ]);
        // Calculate first (while still editable), then approve
        CostingCalculatorService::recalculateCosting($costing);
        $costing->update([
            'status' => 'approved',
            'submitted_by' => $admin->id,
            'submitted_at' => now()->subHour(),
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

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
            'description' => 'Safari Jacket Pro',
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
            'notes' => 'Zippers and fabric for Jacket production',
        ]);

        PoSupplierItem::create([
            'po_supplier_id' => $poSupplier->id,
            'material_id' => $matFabric->id,
            'description' => 'Steel Sheet 2mm',
            'qty' => 1500,
            'unit' => 'kg',
            'unit_price' => 38000,
            'total_price' => 57000000,
            'qty_received' => 0,
        ]);

        PoSupplierItem::create([
            'po_supplier_id' => $poSupplier->id,
            'material_id' => $matZipper->id,
            'description' => 'Metal Zipper',
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
            'description' => 'Assembly Service',
            'qty' => 1000,
            'unit_price' => 15000,
            'total_price' => 15000000,
        ]);

        // 14. Seed Purchase Shipment
        PurchaseShipment::create([
            'company_id' => $kei->id,
            'shipment_number' => CodeGenerator::generatePurchaseShipmentNo(),
            'po_type' => 'supplier',
            'po_id' => $poSupplier->id,
            'shipment_date' => now()->toDateString(),
            'status' => 'shipped',
            'carrier' => 'JNE Cargo',
            'tracking_number' => 'JNE-12345678',
            'shipping_cost' => 120000,
            'eta' => now()->addDays(5)->toDateString(),
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
            'unit' => 'kg',
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
            'unit' => 'kg',
        ]);

        // 17. Seed Subcon Material IN
        $subconIn = SubconMaterialIn::create([
            'company_id' => $kei->id,
            'po_subcon_id' => $poSubcon->id,
            'subcon_material_out_id' => $subconOut->id,
            'subcon_id' => $subconJaya->id,
            'document_number' => 'MAT-IN-001',
            'receive_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        // Processed goods (Assembly) received
        SubconMaterialInItem::create([
            'subcon_material_in_id' => $subconIn->id,
            'material_id' => null,
            'description' => 'Assembly Service',
            'item_type' => 'processed',
            'qty_received' => 195,
            'qty_rejected' => 2,
            'unit' => 'pcs',
        ]);

        // Leftover raw material (Steel) returned
        SubconMaterialInItem::create([
            'subcon_material_in_id' => $subconIn->id,
            'material_id' => $matFabric->id,
            'description' => null,
            'item_type' => 'raw_return',
            'qty_received' => 3, // 3 kg leftover returned
            'qty_rejected' => 0,
            'unit' => 'kg',
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
            'unit' => 'kg',
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
            'unit' => 'pcs',
            'min_stock' => 200,
            'location' => 'Rack C-3',
        ]);

        // 20. Seed Phase 2: Production Orders
        $merchandisingPlanning = MerchandisePlanning::where('project_id', $project->id)->first();

        $productionOrder = ProductionOrder::create([
            'company_id' => $kei->id,
            'production_number' => CodeGenerator::generateProductionOrderNumber(),
            'project_id' => $project->id,
            'merchandising_planning_id' => $merchandisingPlanning ? $merchandisingPlanning->id : null,
            'planned_qty' => 1000,
            'completed_qty' => 0,
            'status' => 'planned',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(45)->toDateString(),
            'notes' => 'Mass production for Nike order',
        ]);

        if ($merchandisingPlanning) {
            foreach ($merchandisingPlanning->items as $item) {
                if ($item->material_id) {
                    ProductionOrderMaterial::create([
                        'company_id' => $kei->id,
                        'production_order_id' => $productionOrder->id,
                        'merchandising_planning_item_id' => $item->id,
                        'material_id' => $item->material_id,
                        'planned_qty' => $item->planned_qty,
                        'unit' => $item->unit,
                        'is_selected' => true,
                    ]);
                }
            }
        }

        // 21. Seed Phase 2: Job Orders
        $jobOrderCutting = JobOrder::create([
            'company_id' => $kei->id,
            'production_order_id' => $productionOrder->id,
            'merchandising_planning_id' => $merchandisingPlanning ? $merchandisingPlanning->id : null,
            'job_order_number' => CodeGenerator::generateJobOrderNumber(),
            'task_type' => 'preparation',
            'planned_qty' => 1000,
            'completed_qty' => 0,
            'status' => 'pending',
            'assigned_to' => 'Preparation Team A',
        ]);

        $jobOrderSewing = JobOrder::create([
            'company_id' => $kei->id,
            'production_order_id' => $productionOrder->id,
            'merchandising_planning_id' => $merchandisingPlanning ? $merchandisingPlanning->id : null,
            'job_order_number' => CodeGenerator::generateJobOrderNumber(),
            'task_type' => 'assembly',
            'planned_qty' => 1000,
            'completed_qty' => 0,
            'status' => 'pending',
            'assigned_to' => 'Assembly Team B',
        ]);

        $jobOrderFinishing = JobOrder::create([
            'company_id' => $kei->id,
            'production_order_id' => $productionOrder->id,
            'merchandising_planning_id' => $merchandisingPlanning ? $merchandisingPlanning->id : null,
            'job_order_number' => CodeGenerator::generateJobOrderNumber(),
            'task_type' => 'quality_control',
            'planned_qty' => 1000,
            'completed_qty' => 0,
            'status' => 'pending',
            'assigned_to' => 'Quality Control Team C',
        ]);

        if ($merchandisingPlanning) {
            foreach ($merchandisingPlanning->items as $item) {
                if ($item->material_id) {
                    JobOrderMaterial::create([
                        'company_id' => $kei->id,
                        'job_order_id' => $jobOrderCutting->id,
                        'merchandising_planning_item_id' => $item->id,
                        'material_id' => $item->material_id,
                        'planned_qty' => $item->planned_qty,
                        'unit' => $item->unit,
                        'is_selected' => true,
                    ]);
                    JobOrderMaterial::create([
                        'company_id' => $kei->id,
                        'job_order_id' => $jobOrderSewing->id,
                        'merchandising_planning_item_id' => $item->id,
                        'material_id' => $item->material_id,
                        'planned_qty' => $item->planned_qty,
                        'unit' => $item->unit,
                        'is_selected' => true,
                    ]);
                    JobOrderMaterial::create([
                        'company_id' => $kei->id,
                        'job_order_id' => $jobOrderFinishing->id,
                        'merchandising_planning_item_id' => $item->id,
                        'material_id' => $item->material_id,
                        'planned_qty' => $item->planned_qty,
                        'unit' => $item->unit,
                        'is_selected' => true,
                    ]);
                }
            }
        }

        // 22. Seed Phase 2: QC Inspections
        $qcInspection = QcInspection::create([
            'company_id' => $kei->id,
            'job_order_id' => $jobOrderFinishing->id,
            'inspection_number' => CodeGenerator::generateQcInspectionNumber(),
            'inspection_date' => now()->addDays(40)->toDateString(),
            'sample_size' => 50,
            'passed_qty' => 48,
            'failed_qty' => 2,
            'result' => 'pass',
            'inspector' => 'QC Supervisor',
            'notes' => 'Initial quality check passed with minor defects',
        ]);

        // 23. Seed Invoice Purchases & Payments
        $invoicePurchase = \App\Models\InvoicePurchase::create([
            'company_id' => $kei->id,
            'invoice_number' => 'INV-PUR-' . now()->format('Ymd') . '-001',
            'purchase_type' => 'po_supplier',
            'reference_id' => $poSupplier->id,
            'invoice_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDays(20)->toDateString(),
            'subtotal' => $poSupplier->subtotal,
            'tax_amount' => $poSupplier->ppn_amount,
            'grand_total' => $poSupplier->grand_total,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'notes' => 'Invoice for raw materials purchase',
        ]);

        \App\Models\Payment::create([
            'company_id' => $kei->id,
            'invoice_type' => 'purchase',
            'invoice_id' => $invoicePurchase->id,
            'payment_number' => 'PAY-PUR-' . now()->format('Ymd') . '-001',
            'payment_date' => now()->subDays(5)->toDateString(),
            'amount' => 20000000.00,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'TRF-100293847',
            'notes' => 'Partial payment for supplier invoice',
        ]);

        \App\Models\Payment::create([
            'company_id' => $kei->id,
            'invoice_type' => 'sales',
            'invoice_id' => $invoiceSales->id,
            'payment_number' => 'PAY-SLS-' . now()->format('Ymd') . '-001',
            'payment_date' => now()->subDays(7)->toDateString(),
            'amount' => $salesOrder->down_payment_amount,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'TRF-9908871',
            'notes' => '30% Down Payment for SO ' . $salesOrder->so_number,
        ]);

        // 24. Seed Shipment (Sales / Delivery)
        \App\Models\Shipment::create([
            'company_id' => $kei->id,
            'shipment_number' => 'SHP-SLS-' . now()->format('Ymd') . '-001',
            'sales_order_id' => $salesOrder->id,
            'shipment_date' => now()->subDays(2)->toDateString(),
            'status' => 'in_transit',
            'shipping_method' => 'sea',
            'carrier' => 'Meratus Line',
            'container_number' => 'MRTS-9920381',
            'bl_number' => 'BL-MRTS-9901',
            'port_of_loading' => 'Tanjung Priok, Jakarta',
            'port_of_discharge' => 'Port of Los Angeles, USA',
            'etd' => now()->subDays(2)->toDateString(),
            'eta' => now()->addDays(28)->toDateString(),
            'total_packages' => 100,
            'total_gross_weight_kg' => 2500,
            'total_volume_m3' => 15.5,
            'shipping_cost_usd' => 3500.00,
            'notes' => 'Export shipment of Nike Jackets',
        ]);

        // 25. Seed Material Usage
        \App\Models\MaterialUsage::create([
            'company_id' => $kei->id,
            'job_order_id' => $jobOrderCutting->id,
            'material_id' => $matFabric->id,
            'usage_date' => now()->subDays(3)->toDateString(),
            'planned_qty' => 1500.00,
            'actual_qty' => 1510.00,
            'waste_qty' => 10.00,
            'unit' => 'yard',
            'unit_price' => $matFabric->price,
            'total_cost' => 1510.00 * $matFabric->price,
            'status' => 'completed',
            'notes' => 'Fabric usage for cutting department',
        ]);

        // 26. Seed Stock Transfer & Stock Transfer Item
        $stockTransfer = \App\Models\StockTransfer::create([
            'from_company_id' => $kei->id,
            'to_company_id' => $ktk->id,
            'from_warehouse_id' => $whMain->id,
            'to_warehouse_id' => $whMainKtk->id,
            'transfer_number' => 'ST-' . now()->format('Ymd') . '-001',
            'transfer_date' => now()->subDays(1)->toDateString(),
            'status' => 'received',
            'notes' => 'Inter-company transfer of zippers for production support',
        ]);

        \App\Models\StockTransferItem::create([
            'stock_transfer_id' => $stockTransfer->id,
            'material_id' => $matZipper->id,
            'qty_requested' => 100.00,
            'qty_transferred' => 100.00,
            'unit' => 'pcs',
        ]);

        // 27. Seed manual adjustment in Inventory Movement
        $stockZipper = \App\Models\InventoryStock::where('warehouse_id', $whMain->id)
            ->where('material_id', $matZipper->id)
            ->first();

        if ($stockZipper) {
            \App\Models\InventoryMovement::create([
                'company_id' => $kei->id,
                'inventory_stock_id' => $stockZipper->id,
                'material_id' => $matZipper->id,
                'type' => 'adjustment',
                'reference_type' => null,
                'reference_id' => null,
                'quantity' => 50.00,
                'notes' => 'Manual stock count adjustment (+50 pcs)',
            ]);
        }
    }
}
