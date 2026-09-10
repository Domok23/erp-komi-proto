<?php

namespace Database\Seeders;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Company;
use App\Models\ConsumptionRate;
use App\Models\Costing;
use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceiptShipping;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\InvoicePurchase;
use App\Models\InvoiceSales;
use App\Models\JobOrder;
use App\Models\JobOrderMaterial;
use App\Models\Material;
use App\Models\MaterialLeftover;
use App\Models\MaterialReservation;
use App\Models\MaterialUsage;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\Payment;
use App\Models\PoSubcon;
use App\Models\PoSubconItem;
use App\Models\PoSupplier;
use App\Models\PoSupplierApproval;
use App\Models\PoSupplierItem;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\Project;
use App\Models\PurchaseShipment;
use App\Models\QcInspection;
use App\Models\RdDesign;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Shipment;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Subcon;
use App\Models\SubconMaterialIn;
use App\Models\SubconMaterialInItem;
use App\Models\SubconMaterialOut;
use App\Models\SubconMaterialOutItem;
use App\Models\SubProject;
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
        if (! $whMain) {
            $whMain = Warehouse::create([
                'company_id' => $kei->id,
                'code' => 'WH-MAIN',
                'name' => 'Main Warehouse',
                'address' => 'Bandung Main Office',
                'is_active' => true,
            ]);
        }

        $whBranch = Warehouse::where('code', 'WH-BRANCH')->where('company_id', $kei->id)->first();
        if (! $whBranch) {
            $whBranch = Warehouse::create([
                'company_id' => $kei->id,
                'code' => 'WH-BRANCH',
                'name' => 'Branch Warehouse',
                'address' => 'Bandung Branch Office',
                'is_active' => true,
            ]);
        }

        $whMainKtk = Warehouse::where('code', 'WH-MAIN-KTK')->where('company_id', $ktk->id)->first();
        if (! $whMainKtk) {
            $whMainKtk = Warehouse::create([
                'company_id' => $ktk->id,
                'code' => 'WH-MAIN-KTK',
                'name' => 'Main Warehouse KTK',
                'address' => 'Jakarta Main Office',
                'is_active' => true,
            ]);
        }

        $whBranchKtk = Warehouse::where('code', 'WH-BRANCH-KTK')->where('company_id', $ktk->id)->first();
        if (! $whBranchKtk) {
            $whBranchKtk = Warehouse::create([
                'company_id' => $ktk->id,
                'code' => 'WH-BRANCH-KTK',
                'name' => 'Branch Warehouse KTK',
                'address' => 'Jakarta Branch Office',
                'is_active' => true,
            ]);
        }

        // 2. Seed Suppliers
        $supplierYKK = Supplier::where('code', 'SUP-001')->where('company_id', $kei->id)->first();
        if (! $supplierYKK) {
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
        if (! $supplierDuraflex) {
            $supplierDuraflex = Supplier::create([
                'company_id' => $kei->id,
                'code' => 'SUP-002',
                'name' => 'PT Supplier Inc',
                'contact_person' => fake()->name(),
                'address' => fake()->address(),
                'is_active' => true,
            ]);
        }

        $supplierTextile = Supplier::where('code', 'SUP-003')->where('company_id', $kei->id)->first();
        if (! $supplierTextile) {
            $supplierTextile = Supplier::create([
                'company_id' => $kei->id,
                'code' => 'SUP-003',
                'name' => 'PT Tekstil Prima Abadi',
                'contact_person' => fake()->name(),
                'address' => fake()->address(),
                'is_active' => true,
            ]);
        }

        $supplierThread = Supplier::where('code', 'SUP-004')->where('company_id', $kei->id)->first();
        if (! $supplierThread) {
            $supplierThread = Supplier::create([
                'company_id' => $kei->id,
                'code' => 'SUP-004',
                'name' => 'CV Benang Jaya Sentosa',
                'contact_person' => fake()->name(),
                'address' => fake()->address(),
                'is_active' => true,
            ]);
        }

        // 3. Seed Subcons
        $subconJaya = Subcon::where('code', 'SUB-001')->where('company_id', $kei->id)->first();
        if (! $subconJaya) {
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

        $subconSablon = Subcon::where('code', 'SUB-002')->where('company_id', $kei->id)->first();
        if (! $subconSablon) {
            $subconSablon = Subcon::create([
                'company_id' => $kei->id,
                'code' => 'SUB-002',
                'name' => 'PT Sablon Citra Mandiri',
                'service_type' => 'printing',
                'contact_person' => fake()->name(),
                'address' => 'Kawasan Industri Cimahi, Bandung',
                'is_active' => true,
            ]);
        }

        // 4. Seed Customers
        $customerVera = Customer::where('code', 'CUS-001')->where('company_id', $kei->id)->first();
        if (! $customerVera) {
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

        $customerYonex = Customer::where('code', 'CUS-002')->where('company_id', $kei->id)->first();
        if (! $customerYonex) {
            $customerYonex = Customer::create([
                'company_id' => $kei->id,
                'code' => 'CUS-002',
                'name' => 'Yonex',
                'contact_person' => fake()->name(),
                'address' => fake()->address(),
                'payment_terms' => 'net_60',
                'is_active' => true,
            ]);
        }

        // 5. Seed Materials
        $matFabric = Material::where('code', 'FAB-001')->first();
        if (! $matFabric) {
            $matFabric = Material::create([
                'code' => 'FAB-001',
                'name' => 'Kain Polyester Hitam',
                'category' => 'fabric',
                'uom' => 'yard',
                'stock' => 1000,
                'min_stock' => 100,
                'price' => 38000,
                'supplier_id' => $supplierYKK->id,
            ]);
        }

        $matZipper = Material::where('code', 'ZIP-001')->first();
        if (! $matZipper) {
            $matZipper = Material::create([
                'code' => 'ZIP-001',
                'name' => 'Metal Zipper',
                'category' => 'zipper',
                'uom' => 'pcs',
                'stock' => 5000,
                'min_stock' => 500,
                'price' => 7500,
                'supplier_id' => $supplierYKK->id,
            ]);
        }

        $matWebbing = Material::where('code', 'ACC-003')->first();
        if (! $matWebbing) {
            $matWebbing = Material::create([
                'code' => 'ACC-003',
                'name' => 'Kancing Premium',
                'category' => 'other',
                'uom' => 'pcs',
                'stock' => 2000,
                'min_stock' => 200,
                'price' => 2500,
                'supplier_id' => $supplierDuraflex->id,
            ]);
        }

        $matFabricEmbroidered = Material::where('code', 'FAB-001-EMB')->first();
        if (! $matFabricEmbroidered) {
            $matFabricEmbroidered = Material::create([
                'code' => 'FAB-001-EMB',
                'name' => 'Kain Polyester Merah (Embroidered)',
                'category' => 'semi_finished',
                'uom' => 'pcs',
                'stock' => 0,
                'min_stock' => 0,
                'price' => 45000,
                'description' => 'Fabric after embroidery processing at subcontractor',
            ]);
        }

        // Demo Materials from seeder_for_demo.xlsx
        $demoMaterials = [
            [
                'code' => 'FAB00103',
                'name' => 'N DOBBY 1335R',
                'category' => 'fabric',
                'uom' => 'yard',
                'size' => '56"',
                'color' => 'SURF THE WEB BLUE',
                'is_import' => true,
                'stock' => 1200,
                'min_stock' => 150,
                'price' => 42000,
                'supplier_id' => $supplierTextile->id,
            ],
            [
                'code' => 'FAB00104',
                'name' => 'N DOBBY 1335R',
                'category' => 'fabric',
                'uom' => 'yard',
                'size' => '56"',
                'color' => 'LAVA SMOKE GRAY',
                'is_import' => true,
                'stock' => 850,
                'min_stock' => 100,
                'price' => 42000,
                'supplier_id' => $supplierTextile->id,
            ],
            [
                'code' => 'HWB0125',
                'name' => 'PLASTIC CORD LOCK gs124',
                'category' => 'hardware',
                'uom' => 'pcs',
                'size' => null,
                'color' => 'BLACK',
                'is_import' => false,
                'stock' => 3500,
                'min_stock' => 300,
                'price' => 1800,
                'supplier_id' => $supplierDuraflex->id,
            ],
            [
                'code' => 'HWB01259',
                'name' => 'PLASTIC CORD LOCK gs124 v2',
                'category' => 'hardware',
                'uom' => 'pcs',
                'size' => null,
                'color' => 'BLACK',
                'is_import' => false,
                'stock' => 2000,
                'min_stock' => 200,
                'price' => 1900,
                'supplier_id' => $supplierDuraflex->id,
            ],
            [
                'code' => 'THB00002-BLU',
                'name' => '100% POLYESTER FILAMENT PF 250/3 2000M',
                'category' => 'thread',
                'uom' => 'cone',
                'size' => 'TEX 80',
                'color' => 'SURF THE WEB BLUE',
                'is_import' => false,
                'stock' => 450,
                'min_stock' => 50,
                'price' => 28000,
                'supplier_id' => $supplierThread->id,
            ],
            [
                'code' => 'THB00002-BLK',
                'name' => '100% POLYESTER FILAMENT PF 250/3 2000M',
                'category' => 'thread',
                'uom' => 'cone',
                'size' => 'TEX 80',
                'color' => 'BLACK C9760 - A SGY 0279',
                'is_import' => false,
                'stock' => 600,
                'min_stock' => 60,
                'price' => 28000,
                'supplier_id' => $supplierThread->id,
            ],
            [
                'code' => 'THB00002-GRY',
                'name' => '100% POLYESTER FILAMENT PF 250/3 2000M',
                'category' => 'thread',
                'uom' => 'cone',
                'size' => 'TEX 80',
                'color' => 'LAVA SMOKE GRAY',
                'is_import' => false,
                'stock' => 350,
                'min_stock' => 40,
                'price' => 28000,
                'supplier_id' => $supplierThread->id,
            ],
            [
                'code' => 'THB00004',
                'name' => '100% POLYESTER FILAMENT PF 210/2 2500M',
                'category' => 'thread',
                'uom' => 'cone',
                'size' => 'TEX 40',
                'color' => 'ANY',
                'is_import' => false,
                'stock' => 500,
                'min_stock' => 50,
                'price' => 24000,
                'supplier_id' => $supplierThread->id,
            ],
            [
                'code' => 'WBB00367',
                'name' => 'CORDING 4.5MM',
                'category' => 'webbing',
                'uom' => 'yard',
                'size' => '4.5MM',
                'color' => 'BLACK',
                'is_import' => false,
                'stock' => 1500,
                'min_stock' => 150,
                'price' => 6500,
                'supplier_id' => $supplierYKK->id,
            ],
            [
                'code' => 'WBB00398',
                'name' => 'FO0380 - PP WEBBING THINPLAIN 22MM',
                'category' => 'webbing',
                'uom' => 'yard',
                'size' => '22MM',
                'color' => 'BLACK',
                'is_import' => false,
                'stock' => 2200,
                'min_stock' => 200,
                'price' => 8500,
                'supplier_id' => $supplierYKK->id,
            ],
        ];

        foreach ($demoMaterials as $item) {
            $mat = Material::where('code', $item['code'])->first();
            if (! $mat) {
                Material::create($item);
            }
        }

        // 6. Seed RdDesigns
        $designBackpack = RdDesign::firstOrCreate(
            ['code' => 'DSN-EBP-001'],
            [
                'company_id' => $kei->id,
                'name' => 'Explorer Backpack Pro',
                'description' => 'High-performance tactical explorer backpack design',
                'product_type' => 'backpack',
                'version' => '1.0',
                'status' => 'approved',
                'brand' => 'Safari',
                'size_range' => '35L',
                'notes' => 'Approved R&D bag model',
            ]
        );

        // 6.1 Seed Consumption Rates (Direct R&D formula)
        if ($designBackpack->consumptionRates()->count() === 0) {
            ConsumptionRate::create([
                'company_id' => $kei->id,
                'design_id' => $designBackpack->id,
                'material_id' => $matFabric->id,
                'component' => 'Main Body Panel',
                'standard_rate' => 1.5,
                'unit' => 'kg',
                'wastage_rate' => 5,
                'notes' => 'Cordura fabric main body',
            ]);

            ConsumptionRate::create([
                'company_id' => $kei->id,
                'design_id' => $designBackpack->id,
                'material_id' => $matZipper->id,
                'component' => 'Zipper Main Compartment',
                'standard_rate' => 3,
                'unit' => 'pcs',
                'wastage_rate' => 2,
                'notes' => 'YKK Heavy duty zipper',
            ]);

            ConsumptionRate::create([
                'company_id' => $kei->id,
                'design_id' => $designBackpack->id,
                'material_id' => $matWebbing->id,
                'component' => 'Shoulder & Chest Harness Webbing',
                'standard_rate' => 6,
                'unit' => 'pcs',
                'wastage_rate' => 0,
                'notes' => 'Reinforced webbing harness',
            ]);
        }

        $designTote = RdDesign::firstOrCreate(
            ['code' => 'DSN-TOT-001'],
            [
                'company_id' => $kei->id,
                'name' => 'Adidas Tote Performance',
                'description' => 'Lightweight durable tote bag design',
                'product_type' => 'tote_bag',
                'version' => '1.0',
                'status' => 'approved',
                'brand' => 'Adidas',
                'size_range' => '20L',
                'notes' => 'Approved R&D tote model',
            ]
        );

        if ($designTote->consumptionRates()->count() === 0) {
            ConsumptionRate::create([
                'company_id' => $kei->id,
                'design_id' => $designTote->id,
                'material_id' => $matFabric->id,
                'component' => 'Tote Shell Canvas',
                'standard_rate' => 0.8,
                'unit' => 'kg',
                'wastage_rate' => 3,
                'notes' => 'Tote canvas fabric',
            ]);

            ConsumptionRate::create([
                'company_id' => $kei->id,
                'design_id' => $designTote->id,
                'material_id' => $matWebbing->id,
                'component' => 'Handle Webbing',
                'standard_rate' => 2,
                'unit' => 'pcs',
                'wastage_rate' => 1,
                'notes' => 'Shoulder handles',
            ]);
        }

        $designLavaGray = RdDesign::firstOrCreate(
            ['code' => 'DSN-LSG-002'],
            [
                'company_id' => $kei->id,
                'name' => 'Racket Cover - Lava Gray Spec',
                'description' => 'Variant spec with reinforced webbing',
                'product_type' => 'backpack',
                'version' => '1.1',
                'status' => 'approved',
                'brand' => 'Wilson',
                'size_range' => 'Standard',
                'notes' => 'R&D override spec for colorway',
            ]
        );

        if ($designLavaGray->consumptionRates()->count() === 0) {
            ConsumptionRate::create([
                'company_id' => $kei->id,
                'design_id' => $designLavaGray->id,
                'material_id' => $matFabric->id,
                'component' => 'Lava Gray Fabric Body',
                'standard_rate' => 1.2,
                'unit' => 'kg',
                'wastage_rate' => 4,
                'notes' => 'Gray tone fabric',
            ]);

            ConsumptionRate::create([
                'company_id' => $kei->id,
                'design_id' => $designLavaGray->id,
                'material_id' => $matZipper->id,
                'component' => 'Side Pocket Zipper',
                'standard_rate' => 2,
                'unit' => 'pcs',
                'wastage_rate' => 2,
                'notes' => 'YKK standard zipper',
            ]);
        }

        // 7. Seed BOMs and BOM Items (Historical DB Retention)
        $bomBackpack = Bom::firstOrCreate(
            ['design_id' => $designBackpack->id],
            [
                'company_id' => $kei->id,
                'bom_number' => CodeGenerator::generateBOMNumber($designBackpack->id, '1.0'),
                'name' => 'Main BOM Explorer Backpack',
                'version' => '1.0',
                'status' => 'active',
            ]
        );

        if ($bomBackpack->items()->count() === 0) {
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
        }

        // 8. Seed Projects & SubProjects
        $project = Project::create([
            'company_id' => $kei->id,
            'project_code' => 'PRJ-2026-001',
            'name' => 'Nike Backpack Elite',
            'description' => 'Mass production order for 1,000 units',
            'type' => 'mass',
            'status' => 'production',
            'customer_id' => $customerVera->id,
            'design_id' => $designBackpack->id,
            'target_qty' => 1000,
        ]);

        $subProject1 = SubProject::create([
            'company_id' => $kei->id,
            'project_id' => $project->id,
            'code' => 'SUB-PRJ-001',
            'name' => 'Front & Sleeve Panel Assembly',
            'category' => 'sub_assembly',
            'design_id' => null,
            'target_qty' => 1000,
            'produced_qty' => 750,
        ]);

        $subProject2 = SubProject::create([
            'company_id' => $kei->id,
            'project_id' => $project->id,
            'code' => 'SUB-PRJ-002',
            'name' => 'Main Body Sewing & Finishing',
            'category' => 'assembly',
            'design_id' => null,
            'target_qty' => 1000,
            'produced_qty' => 500,
        ]);

        $project2 = Project::create([
            'company_id' => $kei->id,
            'project_code' => 'PRJ-2026-002',
            'name' => 'Racket Soft Cover Bag',
            'description' => 'Sample validation production for 200 units',
            'type' => 'sample',
            'status' => 'approved',
            'customer_id' => $customerVera->id,
            'design_id' => $designBackpack->id,
            'target_qty' => 200,
        ]);

        $subProject2_1 = SubProject::create([
            'company_id' => $kei->id,
            'project_id' => $project2->id,
            'code' => 'LSG-10',
            'name' => 'LAVA SMOKE GRAY (NO POCKET)',
            'category' => 'colorway',
            'design_id' => $designLavaGray->id,
            'target_qty' => 100,
            'produced_qty' => 50,
        ]);

        $subProject2_2 = SubProject::create([
            'company_id' => $kei->id,
            'project_id' => $project2->id,
            'code' => 'STWB-01',
            'name' => 'SURF THE WEB BLUE (NO POCKET)',
            'category' => 'colorway',
            'design_id' => null,
            'target_qty' => 100,
            'produced_qty' => 30,
        ]);

        $project3 = Project::create([
            'company_id' => $kei->id,
            'project_code' => 'PRJ-2026-003',
            'name' => 'Adidas Performance Tote Bag',
            'description' => 'Proto design stage evaluation',
            'type' => 'proto',
            'status' => 'planning',
            'customer_id' => $customerVera->id,
            'design_id' => $designTote->id,
            'target_qty' => 50,
        ]);

        // Seed Material Reservations
        MaterialReservation::create([
            'company_id' => $kei->id,
            'warehouse_id' => $whMain->id,
            'material_id' => $matFabric->id,
            'project_id' => $project->id,
            'sub_project_id' => $subProject1->id,
            'document_number' => CodeGenerator::generateReservationNumber(),
            'reservation_type' => 'project',
            'reserved_qty' => 500.00,
            'status' => 'approved',
            'reservation_date' => now()->subDays(3)->toDateString(),
            'notes' => 'Reserved 500 yards fabric for Sub-Project 1',
        ]);

        MaterialReservation::create([
            'company_id' => $kei->id,
            'warehouse_id' => $whMain->id,
            'material_id' => $matZipper->id,
            'project_id' => $project->id,
            'sub_project_id' => $subProject2->id,
            'document_number' => CodeGenerator::generateReservationNumber(),
            'reservation_type' => 'project',
            'reserved_qty' => 1000.00,
            'status' => 'approved',
            'reservation_date' => now()->subDays(2)->toDateString(),
            'notes' => 'Reserved 1000 pcs zipper for Sub-Project 2',
        ]);

        // 9. Seed Merchandise Plannings
        $planning = MerchandisePlanning::create([
            'company_id' => $kei->id,
            'project_id' => $project->id,
            'sub_project_id' => $subProject1->id,
            'design_id' => $designBackpack->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 79500000,
            'total_subcon_cost' => 15000000,
            'special_instructions' => 'Embroidery to be done by CV Bordir Indonesia',
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'material_id' => $matFabric->id,
            'supplier_id' => $supplierYKK->id,
            'component' => 'Main Body Panel',
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
            'component' => 'Zipper Main Compartment',
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

        $planning2 = MerchandisePlanning::create([
            'company_id' => $kei->id,
            'project_id' => $project2->id,
            'sub_project_id' => $subProject2_1->id,
            'design_id' => $designLavaGray->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 16500000,
            'total_subcon_cost' => 2000000,
            'special_instructions' => 'Sample batch for client review',
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning2->id,
            'material_id' => $matFabric->id,
            'supplier_id' => $supplierYKK->id,
            'component' => 'Back Body',
            'planned_qty' => 300,
            'unit' => 'kg',
            'unit_price' => 38000,
            'total_price' => 11400000,
            'is_subcon' => false,
            'is_from_rnd' => true,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning2->id,
            'material_id' => $matWebbing->id,
            'supplier_id' => $supplierYKK->id,
            'component' => 'Shoulder & Chest Harness Webbing',
            'planned_qty' => 600,
            'unit' => 'pcs',
            'unit_price' => 8500,
            'total_price' => 5100000,
            'is_subcon' => false,
            'is_from_rnd' => true,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning2->id,
            'subcon_id' => $subconJaya->id,
            'planned_qty' => 200,
            'unit' => 'pcs',
            'unit_price' => 10000,
            'total_price' => 2000000,
            'is_subcon' => true,
        ]);

        $planning3 = MerchandisePlanning::create([
            'company_id' => $kei->id,
            'project_id' => $project3->id,
            'design_id' => $designTote->id,
            'planning_date' => now()->toDateString(),
            'status' => 'preliminary',
            'total_material_cost' => 3800000,
            'total_subcon_cost' => 0,
            'special_instructions' => 'Draft planning under R&D evaluation',
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning3->id,
            'material_id' => $matFabric->id,
            'supplier_id' => $supplierYKK->id,
            'planned_qty' => 100,
            'unit' => 'kg',
            'unit_price' => 38000,
            'total_price' => 3800000,
            'is_subcon' => false,
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

        $costingTote = Costing::create([
            'company_id' => $kei->id,
            'project_id' => $project3->id,
            'design_id' => $designTote->id,
            'costing_date' => now()->subDays(5)->toDateString(),
            'version' => '1.0',
            'status' => 'draft',
            'material_cost' => 76000,
            'mp_cost' => 33000,
            'overhead_pct' => 15,
            'shipping_cost' => 5000,
            'profit_margin_pct' => 20,
            'currency' => 'IDR',
        ]);
        CostingCalculatorService::recalculateCosting($costingTote);
        $costingTote->update([
            'status' => 'approved',
            'submitted_by' => $admin->id,
            'submitted_at' => now()->subDays(4),
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(3),
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

        $salesOrder2 = SalesOrder::create([
            'company_id' => $kei->id,
            'so_number' => 'SO-2026-002',
            'project_id' => $project3->id,
            'costing_id' => $costingTote->id,
            'customer_id' => $customerVera->id,
            'order_date' => now()->startOfMonth()->addDays(2)->toDateString(),
            'delivery_date' => now()->addDays(45)->toDateString(),
            'quantity' => 500,
            'unit_price' => $costingTote->selling_price,
            'status' => 'confirmed',
            'currency' => 'IDR',
            'exchange_rate' => 1,
            'subtotal' => 500 * $costingTote->selling_price,
            'ppn_percent' => 11,
            'ppn_amount' => (500 * $costingTote->selling_price) * 0.11,
            'shipping_cost' => 1500000,
            'grand_total' => (500 * $costingTote->selling_price) * 1.11 + 1500000,
            'down_payment_pct' => 30,
            'down_payment_amount' => ((500 * $costingTote->selling_price) * 1.11 + 1500000) * 0.30,
            'payment_terms' => 'dp_30',
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $salesOrder2->id,
            'description' => 'Adidas Performance Tote Bag',
            'quantity' => 500,
            'unit' => 'pcs',
            'unit_price' => $costingTote->selling_price,
            'total_price' => 500 * $costingTote->selling_price,
        ]);
        $project3->update(['sales_order_id' => $salesOrder2->id]);

        $salesOrder3 = SalesOrder::create([
            'company_id' => $kei->id,
            'so_number' => 'SO-2026-003',
            'project_id' => $project->id,
            'costing_id' => $costing->id,
            'customer_id' => $customerVera->id,
            'order_date' => now()->subMonth()->subDays(5)->toDateString(),
            'delivery_date' => now()->addDays(15)->toDateString(),
            'quantity' => 800,
            'unit_price' => $costing->selling_price,
            'status' => 'in_production',
            'currency' => 'IDR',
            'exchange_rate' => 1,
            'subtotal' => 800 * $costing->selling_price,
            'ppn_percent' => 11,
            'ppn_amount' => (800 * $costing->selling_price) * 0.11,
            'shipping_cost' => 2000000,
            'grand_total' => (800 * $costing->selling_price) * 1.11 + 2000000,
            'down_payment_pct' => 30,
            'down_payment_amount' => ((800 * $costing->selling_price) * 1.11 + 2000000) * 0.30,
            'payment_terms' => 'dp_30',
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $salesOrder3->id,
            'description' => 'Nike Backpack Elite (Batch 2)',
            'quantity' => 800,
            'unit' => 'pcs',
            'unit_price' => $costing->selling_price,
            'total_price' => 800 * $costing->selling_price,
        ]);

        // 12. Seed PO Suppliers (Multi-Project Consolidated PO)
        $poSupplier = PoSupplier::create([
            'company_id' => $kei->id,
            'po_number' => CodeGenerator::generatePOSupplierNo(),
            'project_id' => $project->id,
            'project_ids' => [$project->id, $project2->id],
            'supplier_id' => $supplierYKK->id,
            'po_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(20)->toDateString(),
            'status' => 'ordered',
            'approval_status' => 'approved',
            'subtotal' => 90900000,
            'ppn_percent' => 11,
            'ppn_amount' => 9999000,
            'grand_total' => 100899000,
            'notes' => 'Consolidated procurement for Nike Backpack & Racket Cover projects',
        ]);

        PoSupplierItem::create([
            'po_supplier_id' => $poSupplier->id,
            'project_id' => $project->id,
            'sub_project_id' => $subProject1->id,
            'material_id' => $matFabric->id,
            'component' => 'Main Body Panel',
            'description' => 'Fabric Black 56 inch',
            'qty' => 1500,
            'unit' => 'kg',
            'unit_price' => 38000,
            'total_price' => 57000000,
            'qty_received' => 0,
        ]);

        PoSupplierItem::create([
            'po_supplier_id' => $poSupplier->id,
            'project_id' => $project2->id,
            'sub_project_id' => $subProject2_1->id,
            'material_id' => $matFabric->id,
            'component' => 'Back Body',
            'description' => 'Fabric Black 56 inch',
            'qty' => 300,
            'unit' => 'kg',
            'unit_price' => 38000,
            'total_price' => 11400000,
            'qty_received' => 0,
        ]);

        PoSupplierItem::create([
            'po_supplier_id' => $poSupplier->id,
            'project_id' => $project->id,
            'sub_project_id' => $subProject2->id,
            'material_id' => $matZipper->id,
            'component' => 'Zipper Main Compartment',
            'description' => 'Metal Zipper #5 YKK',
            'qty' => 3000,
            'unit' => 'pcs',
            'unit_price' => 7500,
            'total_price' => 22500000,
            'qty_received' => 0,
        ]);

        // Seed Approvals for PDF signatures
        PoSupplierApproval::create([
            'po_supplier_id' => $poSupplier->id,
            'user_id' => $admin->id,
            'approval_level' => 'manager',
            'status' => 'approved',
            'actioned_at' => now()->subHour(),
        ]);

        PoSupplierApproval::create([
            'po_supplier_id' => $poSupplier->id,
            'user_id' => $admin->id,
            'approval_level' => 'director',
            'status' => 'approved',
            'actioned_at' => now()->subMinutes(30),
        ]);

        // 13. Seed PO Subcons (Multi-Project Consolidated PO)
        $poSubcon = PoSubcon::create([
            'company_id' => $kei->id,
            'po_number' => CodeGenerator::generatePOSubconNo(),
            'project_id' => $project->id,
            'project_ids' => [$project->id, $project2->id],
            'subcon_id' => $subconJaya->id,
            'po_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(25)->toDateString(),
            'status' => 'ordered',
            'service_cost' => 17000000,
            'shipping_cost' => 500000,
            'shipping_return_cost' => 500000,
            'total_cost' => 18000000,
        ]);

        PoSubconItem::create([
            'po_subcon_id' => $poSubcon->id,
            'project_id' => $project->id,
            'sub_project_id' => $subProject1->id,
            'description' => 'Front Panel Assembly Service',
            'qty' => 1000,
            'unit_price' => 15000,
            'total_price' => 15000000,
        ]);

        PoSubconItem::create([
            'po_subcon_id' => $poSubcon->id,
            'project_id' => $project2->id,
            'sub_project_id' => $subProject2_1->id,
            'description' => 'Handle Webbing Reinforcement Service',
            'qty' => 200,
            'unit_price' => 10000,
            'total_price' => 2000000,
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

        $goodsReceipt->update(['status' => 'verified']);

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
        InventoryStock::updateOrCreate([
            'company_id' => $kei->id,
            'warehouse_id' => $whMain->id,
            'material_id' => $matFabric->id,
        ], [
            'quantity' => 2000,
            'reserved_qty' => 0,
            'available_qty' => 2000,
            'unit' => 'kg',
            'min_stock' => 100,
            'location' => 'Aisle A-1',
        ]);

        InventoryStock::updateOrCreate([
            'company_id' => $kei->id,
            'warehouse_id' => $whMain->id,
            'material_id' => $matZipper->id,
        ], [
            'quantity' => 3000,
            'reserved_qty' => 0,
            'available_qty' => 3000,
            'unit' => 'pcs',
            'min_stock' => 500,
            'location' => 'Bin B-12',
        ]);

        InventoryStock::updateOrCreate([
            'company_id' => $kei->id,
            'warehouse_id' => $whMain->id,
            'material_id' => $matWebbing->id,
        ], [
            'quantity' => 5000,
            'reserved_qty' => 0,
            'available_qty' => 5000,
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
            'completed_qty' => 1000,
            'status' => 'completed',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
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
        $invoicePurchase = InvoicePurchase::create([
            'company_id' => $kei->id,
            'invoice_number' => 'INV-PUR-'.now()->format('Ymd').'-001',
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

        Payment::create([
            'company_id' => $kei->id,
            'invoice_type' => 'purchase',
            'invoice_id' => $invoicePurchase->id,
            'payment_number' => 'PAY-PUR-'.now()->format('Ymd').'-001',
            'payment_date' => now()->subDays(5)->toDateString(),
            'amount' => 20000000.00,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'TRF-100293847',
            'notes' => 'Partial payment for supplier invoice',
        ]);

        Payment::create([
            'company_id' => $kei->id,
            'invoice_type' => 'sales',
            'invoice_id' => $invoiceSales->id,
            'payment_number' => 'PAY-SLS-'.now()->format('Ymd').'-001',
            'payment_date' => now()->subDays(7)->toDateString(),
            'amount' => $salesOrder->down_payment_amount,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'TRF-9908871',
            'notes' => '30% Down Payment for SO '.$salesOrder->so_number,
        ]);

        // 24. Seed Shipment (Sales / Delivery)
        Shipment::create([
            'company_id' => $kei->id,
            'shipment_number' => 'SHP-SLS-'.now()->format('Ymd').'-001',
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
        MaterialUsage::create([
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
        $stockTransfer = StockTransfer::create([
            'from_company_id' => $kei->id,
            'to_company_id' => $ktk->id,
            'from_warehouse_id' => $whMain->id,
            'to_warehouse_id' => $whMainKtk->id,
            'transfer_number' => 'ST-'.now()->format('Ymd').'-001',
            'transfer_date' => now()->subDays(1)->toDateString(),
            'status' => 'received',
            'notes' => 'Inter-company transfer of zippers for production support',
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $stockTransfer->id,
            'material_id' => $matZipper->id,
            'qty_requested' => 100.00,
            'qty_transferred' => 100.00,
            'unit' => 'pcs',
        ]);

        // 27. Seed manual adjustment in Inventory Movement
        $stockZipper = InventoryStock::where('warehouse_id', $whMain->id)
            ->where('material_id', $matZipper->id)
            ->first();

        if ($stockZipper) {
            InventoryMovement::create([
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

        // 28. Seed Material Leftovers (Factory Scraps & Remnants)
        MaterialLeftover::create([
            'company_id' => $kei->id,
            'job_order_id' => $jobOrderCutting->id,
            'material_id' => $matFabric->id,
            'leftover_date' => now()->subDays(2)->toDateString(),
            'qty' => 15.50,
            'unit' => 'yard',
            'condition' => 'usable',
            'status' => 'available',
            'notes' => 'Reusable fabric remnants from cutting batch #1, suitable for pocket pouches',
        ]);

        MaterialLeftover::create([
            'company_id' => $kei->id,
            'job_order_id' => $jobOrderCutting->id,
            'material_id' => $matZipper->id,
            'leftover_date' => now()->subDays(2)->toDateString(),
            'qty' => 25.00,
            'unit' => 'pcs',
            'condition' => 'scrap',
            'status' => 'disposed',
            'notes' => 'Damaged zipper sliders and end cutoffs discarded after quality inspection',
        ]);
    }
}
