<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Material;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectBom;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\Subcon;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DataSeeder extends Seeder
{
    protected $faker;

    protected static $keiId = 1;
    protected static $ktkId = 2;

    public function run(): void
    {
        $this->faker = \Faker\Factory::create('id_ID');

        $this->seedSuppliers();
        $this->seedSubcons();
        $this->seedCustomers();
        $this->seedMaterials();
        $this->seedProducts();
        $this->seedSalesOrders();
        $this->seedPurchaseOrders();
        $this->seedProjects();
        $this->seedProjectBoms();
        $this->seedShipments();
        $this->seedInvoices();
        $this->seedGoodsReceipts();
    }

    // ──────────────────────────────────────────────────────────────
    //  SUPPLIERS
    // ──────────────────────────────────────────────────────────────
    protected function seedSuppliers(): void
    {
        $suppliers = [
            // KEI Suppliers (id=1)
            [
                'company_id' => self::$keiId,
                'code' => 'SUP-001',
                'name' => 'PT YKK Indonesia',
                'contact_person' => 'Budi Santoso',
                'address' => 'Jl. Industri Raya No. 18, Kawasan Industri Pulogadung',
                'city' => 'Jakarta Timur',
                'phone' => '+62 21 460 1234',
                'email' => 'sales@ykk.co.id',
                'npwp' => '01.234.567.8-012.000',
                'bank_account' => 'BCA 1234567890',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'SUP-002',
                'name' => 'PT Amphenol Intertech',
                'contact_person' => 'Hendra Wijaya',
                'address' => 'Jl. Chungang Hypercluster, Blok A-10',
                'city' => 'Jakarta',
                'phone' => '+62 21 2950 8800',
                'email' => 'procurement@amphenol.co.id',
                'npwp' => '02.345.678.9-013.001',
                'bank_account' => 'Mandiri 1300012345678',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'SUP-003',
                'name' => 'PT Duraflex Belting',
                'contact_person' => 'Siti Nurhaliza',
                'address' => 'Jl. Suka Pura Km 5, Nagrek',
                'city' => 'Bandung',
                'phone' => '+62 22 520 3344',
                'email' => 'order@duraflex.co.id',
                'npwp' => '03.456.789.0-014.002',
                'bank_account' => 'BNI 0091234567',
                'is_active' => true,
            ],
            // KTK Suppliers (id=2)
            [
                'company_id' => self::$ktkId,
                'code' => 'SUP-004',
                'name' => 'PT SGT Textile Solutions',
                'contact_person' => 'Asep Hermawan',
                'address' => 'Jl. Mayor Bonjankristi No. 45',
                'city' => 'Surakarta',
                'phone' => '+62 271 655 880',
                'email' => 'sales@sgt.co.id',
                'npwp' => '04.567.890.1-015.003',
                'bank_account' => 'BRI 0021-01-000123-56-7',
                'is_active' => true,
            ],
            [
                'company_id' => self::$ktkId,
                'code' => 'SUP-005',
                'name' => 'PT Intitext Interlining',
                'contact_person' => 'Rina Marlina',
                'address' => 'Kawasan Industri Candiangrang',
                'city' => 'Bandung',
                'phone' => '+62 22 665 4433',
                'email' => 'marketing@intitext.co.id',
                'npwp' => '05.678.901.2-016.004',
                'bank_account' => 'BCA 8812001100',
                'is_active' => true,
            ],
            [
                'company_id' => self::$ktkId,
                'code' => 'SUP-006',
                'name' => 'PT Metalcraft Hardware',
                'contact_person' => 'Dedi Kurniawan',
                'address' => 'Jl. Surya Bangsa Lestari Blok F',
                'city' => 'Tangerang',
                'phone' => '+62 21 599 2211',
                'email' => 'procurement@metalcraft.id',
                'npwp' => '06.789.012.3-017.005',
                'bank_account' => 'BCA 2289013400',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['code' => $supplier['code']],
                $supplier
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  SUBCONS
    // ──────────────────────────────────────────────────────────────
    protected function seedSubcons(): void
    {
        $subcons = [
            // KEI Subcons
            [
                'company_id' => self::$keiId,
                'code' => 'SUB-001',
                'name' => 'CV Jaya Bordir',
                'service_type' => 'embroidery',
                'contact_person' => 'Tono Rahardjo',
                'address' => 'Jl. Melati No. 22 RT 003/005, Jakarta Barat',
                'phone' => '+62 21 634 5577',
                'email' => 'order@jayabordir.com',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'SUB-002',
                'name' => 'PT Satria Screen Printing',
                'service_type' => 'printing',
                'contact_person' => 'Neni Supriatna',
                'address' => 'Kp. Sawah V RT 03/08, Ciganjuri, Depok',
                'phone' => '+62 21 775 8899',
                'email' => 'info@satriaprint.id',
                'is_active' => true,
            ],
            // KTK Subcons
            [
                'company_id' => self::$ktkId,
                'code' => 'SUB-003',
                'name' => 'PT Tunas Garment Sentosa',
                'service_type' => 'sewing',
                'contact_person' => 'Bpk. Heryanto',
                'address' => 'Jl. Padat Karya Kav. 11, Rancamaya, Bogor',
                'phone' => '+62 251 754 3311',
                'email' => 'heryanto@tunasgarment.co.id',
                'is_active' => true,
            ],
        ];

        foreach ($subcons as $subcon) {
            Subcon::updateOrCreate(
                ['code' => $subcon['code']],
                $subcon
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  CUSTOMERS
    // ──────────────────────────────────────────────────────────────
    protected function seedCustomers(): void
    {
        $customers = [
            [
                'company_id' => self::$keiId,
                'code' => 'CUS-001',
                'name' => 'Vera Bradley Exports, Inc.',
                'contact_person' => 'Sarah Mitchell',
                'address' => '1244 Liberty Bell Drive',
                'city' => 'Roanoke, VA',
                'country' => 'United States',
                'phone' => '+1 540 432 5568',
                'email' => 'sourcing@verabradley.com',
                'payment_terms' => 'Net 60',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'CUS-002',
                'name' => 'Kipling North America Corp.',
                'contact_person' => 'James O\'Brien',
                'address' => '200 Liberty Street, Suite 3400',
                'city' => 'New York, NY',
                'country' => 'United States',
                'phone' => '+1 212 555 0190',
                'email' => 'james.obrien@kipling.com',
                'payment_terms' => 'Net 45',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'CUS-003',
                'name' => 'Fjällräven Canada Inc.',
                'contact_person' => 'Lars Eriksson',
                'address' => '100 King Street West, Suite 5600',
                'city' => 'Toronto, ON',
                'country' => 'Canada',
                'phone' => '+1 416 555 0144',
                'email' => 'lars.eriksson@fjallraven.ca',
                'payment_terms' => 'Net 30',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'CUS-004',
                'name' => 'Cotopaxi Global Trading LLC',
                'contact_person' => 'Maria Gonzalez',
                'address' => '455 East 100 South, Suite 400',
                'city' => 'Salt Lake City, UT',
                'country' => 'United States',
                'phone' => '+1 801 555 0233',
                'email' => 'maria@cotopaxi.com',
                'payment_terms' => 'Net 60',
                'is_active' => true,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['code' => $customer['code']],
                $customer
            );
        }
    }

    // ────────────────��─────────────────────────────────────────────
    //  MATERIALS
    // ──────────────────────────────────────────────────────────────
    protected function seedMaterials(): void
    {
        $materials = [
            // ── Fabrics (KEI)
            [
                'company_id' => self::$keiId,
                'code' => 'FAB-001',
                'name' => '600D Recycled Polyester Dobby',
                'category' => 'fabric',
                'unit' => 'yard',
                'stock' => 2500,
                'min_stock' => 500,
                'price' => 3.80,
                'supplier_id' => null, // generic fabric
                'description' => '600D polyester dobby fabric, water-resistant coating, ideal for backpack body. Width: 150cm.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'FAB-002',
                'name' => '1680D Ballistic Nylon',
                'category' => 'fabric',
                'unit' => 'yard',
                'stock' => 1200,
                'min_stock' => 300,
                'price' => 6.50,
                'supplier_id' => null,
                'description' => '1680D ballistic nylon, high abrasion resistance, for premium bag base & high-wear panels. Width: 150cm.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'FAB-003',
                'name' => 'Cotton Canvas 12oz',
                'category' => 'fabric',
                'unit' => 'yard',
                'stock' => 1800,
                'min_stock' => 400,
                'price' => 4.20,
                'supplier_id' => null,
                'description' => '12oz cotton canvas, natural & dyed colors, for handbag outer shell. Width: 145cm.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'FAB-004',
                'name' => 'PU Leather (Artificial Leather)',
                'category' => 'fabric',
                'unit' => 'yard',
                'stock' => 800,
                'min_stock' => 200,
                'price' => 5.60,
                'supplier_id' => null,
                'description' => 'Premium PU leather, matte finish, suitable for tote bag & handbag application. Width: 140cm.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'FAB-005',
                'name' => '210D Ripstop Nylon',
                'category' => 'fabric',
                'unit' => 'yard',
                'stock' => 3000,
                'min_stock' => 600,
                'price' => 2.90,
                'supplier_id' => null,
                'description' => '210D ripstop nylon, lightweight, PU coated, for lining and internal compartments. Width: 150cm.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'FAB-006',
                'name' => 'Polyester Mesh 3D Spacer',
                'category' => 'fabric',
                'unit' => 'yard',
                'stock' => 500,
                'min_stock' => 100,
                'price' => 4.50,
                'supplier_id' => null,
                'description' => '3D spacer mesh, breathable cushion fabric for backpack shoulder straps and back panels.',
                'is_active' => true,
            ],
            // ── Zippers (KEI)
            [
                'company_id' => self::$keiId,
                'code' => 'ZIP-001',
                'name' => 'YKK #5 Metal Zipper, Nickel',
                'category' => 'zipper',
                'unit' => 'pcs',
                'stock' => 5000,
                'min_stock' => 1000,
                'price' => 0.75,
                'supplier_id' => null,
                'description' => 'YKK No.5 metal zipper, nickel finish, for bag main compartments. Standard puller.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'ZIP-002',
                'name' => 'YKK #8 Invisible Zipper',
                'category' => 'zipper',
                'unit' => 'pcs',
                'stock' => 3000,
                'min_stock' => 500,
                'price' => 1.10,
                'supplier_id' => null,
                'description' => 'YKK No.8 invisible zipper for laptop compartment and interior pocket closures.',
                'is_active' => true,
            ],
            // ── Hardware & Accessories (KEI)
            [
                'company_id' => self::$keiId,
                'code' => 'ACC-001',
                'name' => 'Metal D-Ring 38mm, Antique Brass',
                'category' => 'other',
                'unit' => 'pcs',
                'stock' => 4000,
                'min_stock' => 800,
                'price' => 0.35,
                'supplier_id' => null,
                'description' => '38mm metal D-ring, antique brass plating, for strap attachment points.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'ACC-002',
                'name' => 'Quick-Release Buckle 40mm, Black',
                'category' => 'other',
                'unit' => 'pcs',
                'stock' => 6000,
                'min_stock' => 1000,
                'price' => 0.55,
                'supplier_id' => null,
                'description' => '40mm quick-release side release buckle, acetyl plastic frame, black. For backpack sternum strap.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'ACC-003',
                'name' => 'Webbing Tape 38mm Nylon, Black',
                'category' => 'other',
                'unit' => 'meter',
                'stock' => 5000,
                'min_stock' => 1000,
                'price' => 0.25,
                'supplier_id' => null,
                'description' => '38mm nylon webbing tape, tensile strength 500kg, for handles and straps. Black.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'ACC-004',
                'name' => 'Embroidered Woven Label (Custom Logo)',
                'category' => 'label',
                'unit' => 'pcs',
                'stock' => 8000,
                'min_stock' => 2000,
                'price' => 0.18,
                'supplier_id' => null,
                'description' => 'Custom embroidered woven label, satin edge, for bag exterior brand tag. Up to 6 colors.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'THR-001',
                'name' => 'Sewing Thread Nylon 210d/2, Natural',
                'category' => 'thread',
                'unit' => 'cone',
                'stock' => 500,
                'min_stock' => 100,
                'price' => 2.40,
                'supplier_id' => null,
                'description' => '210d/2 nylon sewing thread, high tensile strength, for heavy-duty bag construction. 500m/cone.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'INT-001',
                'name' => 'Non-Woven Interlining 80g/m2',
                'category' => 'interlining',
                'unit' => 'meter',
                'stock' => 3000,
                'min_stock' => 500,
                'price' => 0.85,
                'supplier_id' => null,
                'description' => '80g/m2 non-woven interlining, fusible one-side coating, for bag panel stiffness.',
                'is_active' => true,
            ],
            // ── KTK Materials
            [
                'company_id' => self::$ktkId,
                'code' => 'FAB-007',
                'name' => '300D Polyester Oxford',
                'category' => 'fabric',
                'unit' => 'yard',
                'stock' => 2000,
                'min_stock' => 400,
                'price' => 3.20,
                'supplier_id' => null,
                'description' => '300D polyester oxford fabric, PU coated, for textile bag production. Width: 150cm.',
                'is_active' => true,
            ],
            [
                'company_id' => self::$ktkId,
                'code' => 'FAB-008',
                'name' => 'Polyester Satin 190T',
                'category' => 'fabric',
                'unit' => 'yard',
                'stock' => 1500,
                'min_stock' => 300,
                'price' => 2.60,
                'supplier_id' => null,
                'description' => '190T polyester satin, for lining and inner pockets. Width: 150cm.',
                'is_active' => true,
            ],
        ];

        foreach ($materials as $material) {
            Material::updateOrCreate(
                ['code' => $material['code']],
                $material
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  PRODUCTS
    // ──────────────────────────────────────────────────────────────
    protected function seedProducts(): void
    {
        $products = [
            [
                'company_id' => self::$keiId,
                'code' => 'EBP-001',
                'name' => 'Explorer Backpack Pro',
                'description' => 'High-performance 30L exploration backpack with ergonomic harness system, hydration compatible, YKK zippers throughout.',
                'category' => 'backpack',
                'unit' => 'pcs',
                'weight_kg' => 0.85,
                'dimensions' => '48 x 32 x 20 cm',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'UHS-001',
                'name' => 'Urban Handbag Series',
                'description' => 'Stylish canvas handbag with PU leather trim, multiple compartments, laptop sleeve. Ideal for daily urban commute.',
                'category' => 'handbag',
                'unit' => 'pcs',
                'weight_kg' => 0.55,
                'dimensions' => '40 x 30 x 12 cm',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'SDB-001',
                'name' => 'Sport Duffle Bag Premium',
                'description' => '65L heavy-duty duffle bag with wheels and extendable handle, ballistic nylon base, multiple haul grips.',
                'category' => 'duffle',
                'unit' => 'pcs',
                'weight_kg' => 1.20,
                'dimensions' => '65 x 35 x 35 cm',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'CLB-001',
                'name' => 'Corporate Laptop Backpack',
                'description' => 'Professional laptop backpack (fits up to 17"), anti-thefic zip, padded laptop compartment, USB charging port.',
                'category' => 'laptop_bag',
                'unit' => 'pcs',
                'weight_kg' => 0.90,
                'dimensions' => '45 x 30 x 18 cm',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'TMB-001',
                'name' => 'Travel Messenger Bag',
                'description' => '15" laptop messenger bag with quick-release buckle, waxed canvas exterior, water-resistant liner.',
                'category' => 'messenger',
                'unit' => 'pcs',
                'weight_kg' => 0.70,
                'dimensions' => '42 x 30 x 10 cm',
                'is_active' => true,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'CTB-001',
                'name' => 'Classic Tote Bag',
                'description' => 'Canvas & PU leather classic tote with magnetic snap closure, interior zip pocket, reinforced base.',
                'category' => 'other',
                'unit' => 'pcs',
                'weight_kg' => 0.45,
                'dimensions' => '38 x 35 x 12 cm',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['code' => $product['code']],
                $product
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  SALES ORDERS
    // ──────────────────────────────────────────────────────────────
    protected function seedSalesOrders(): void
    {
        $soData = [
            [
                'so_number' => 'SO-KEI-2025-0001',
                'company_id' => self::$keiId,
                'customer_id' => null, // filled below
                'order_date' => '2025-03-15',
                'delivery_date' => '2025-06-15',
                'status' => 'shipped',
                'currency' => 'USD',
                'exchange_rate' => 1.0,
                'subtotal' => 45600.00,
                'tax_pct' => 0.0,
                'tax_amount' => 0.00,
                'total_amount' => 45600.00,
                'down_payment_pct' => 30.0,
                'down_payment_amount' => 13680.00,
                'payment_terms' => 'Net 60',
                'notes' => 'FOB Jakarta. Order for Vera Bradley Q3 2025 collection.',
            ],
            [
                'so_number' => 'SO-KEI-2025-0002',
                'company_id' => self::$keiId,
                'customer_id' => null,
                'order_date' => '2025-07-20',
                'delivery_date' => '2025-10-20',
                'status' => 'in_production',
                'currency' => 'USD',
                'exchange_rate' => 1.0,
                'subtotal' => 68400.00,
                'tax_pct' => 0.0,
                'tax_amount' => 0.00,
                'total_amount' => 68400.00,
                'down_payment_pct' => 20.0,
                'down_payment_amount' => 13680.00,
                'payment_terms' => 'Net 45',
                'notes' => 'Kipling Q4 holiday order. Mixed models EBP-001 and CLB-001.',
            ],
            [
                'so_number' => 'SO-KEI-2026-0001',
                'company_id' => self::$keiId,
                'customer_id' => null,
                'order_date' => '2026-01-10',
                'delivery_date' => '2026-04-10',
                'status' => 'draft',
                'currency' => 'USD',
                'exchange_rate' => 1.0,
                'subtotal' => 31200.00,
                'tax_pct' => 0.0,
                'tax_amount' => 0.00,
                'total_amount' => 31200.00,
                'down_payment_pct' => 0.0,
                'down_payment_amount' => 0.00,
                'payment_terms' => 'Net 30',
                'notes' => 'Cotopaxi Spring 2026 trial order. Initial PO: 2,000 units UHS-001.',
            ],
        ];

        // Resolve customer IDs
        $veraBradley = Customer::where('code', 'CUS-001')->first();
        $kipling = Customer::where('code', 'CUS-002')->first();
        $cotopaxi = Customer::where('code', 'CUS-004')->first();

        $soData[0]['customer_id'] = $veraBradley->id;
        $soData[1]['customer_id'] = $kipling->id;
        $soData[2]['customer_id'] = $cotopaxi->id;

        foreach ($soData as $so) {
            SalesOrder::updateOrCreate(
                ['so_number' => $so['so_number']],
                $so
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  PURCHASE ORDERS
    // ──────────────────────────────────────────────────────────────
    protected function seedPurchaseOrders(): void
    {
        $ykk = Supplier::where('code', 'SUP-001')->first();
        $amphenol = Supplier::where('code', 'SUP-002')->first();
        $duraflex = Supplier::where('code', 'SUP-003')->first();
        $sgt = Supplier::where('code', 'SUP-004')->first();
        $jayaBordir = Subcon::where('code', 'SUB-001')->first();
        $satriaPrint = Subcon::where('code', 'SUB-002')->first();

        $poData = [
            // Supplier POs
            [
                'po_number' => 'PO-KEI-2025-0001',
                'company_id' => self::$keiId,
                'type' => 'supplier',
                'supplier_id' => $ykk->id,
                'subcon_id' => null,
                'order_date' => '2025-03-20',
                'delivery_date' => '2025-04-25',
                'status' => 'received',
                'currency' => 'USD',
                'subtotal' => 6250.00,
                'tax_amount' => 625.00,
                'total_amount' => 6875.00,
                'notes' => 'YKK zippers for SO-KEI-2025-0001. Mixed #5 and #8.',
            ],
            [
                'po_number' => 'PO-KEI-2025-0002',
                'company_id' => self::$keiId,
                'type' => 'supplier',
                'supplier_id' => $duraflex->id,
                'subcon_id' => null,
                'order_date' => '2025-04-01',
                'delivery_date' => '2025-04-30',
                'status' => 'received',
                'currency' => 'USD',
                'subtotal' => 8500.00,
                'tax_amount' => 850.00,
                'total_amount' => 9350.00,
                'notes' => 'Duraflex webbing, buckles, and D-rings for SO-KEI-2025-0001.',
            ],
            [
                'po_number' => 'PO-KEI-2025-0003',
                'company_id' => self::$keiId,
                'type' => 'supplier',
                'supplier_id' => $amphenol->id,
                'subcon_id' => null,
                'order_date' => '2025-07-25',
                'delivery_date' => '2025-08-20',
                'status' => 'partial',
                'currency' => 'USD',
                'subtotal' => 11200.00,
                'tax_amount' => 1120.00,
                'total_amount' => 12320.00,
                'notes' => 'Amphenol hardware for Kipling Q4 order. Partial delivery in progress.',
            ],
            // Subcon POs
            [
                'po_number' => 'PO-KEI-2025-0004',
                'company_id' => self::$keiId,
                'type' => 'subcon',
                'supplier_id' => null,
                'subcon_id' => $jayaBordir->id,
                'order_date' => '2025-04-05',
                'delivery_date' => '2025-05-10',
                'status' => 'received',
                'currency' => 'USD',
                'subtotal' => 3200.00,
                'tax_amount' => 320.00,
                'total_amount' => 3520.00,
                'notes' => 'Jaya Bordir embroidery for logo labels on SO-KEI-2025-0001 bags.',
            ],
            [
                'po_number' => 'PO-KEI-2025-0005',
                'company_id' => self::$keiId,
                'type' => 'subcon',
                'supplier_id' => null,
                'subcon_id' => $satriaPrint->id,
                'order_date' => '2025-08-01',
                'delivery_date' => '2025-09-01',
                'status' => 'sent',
                'currency' => 'USD',
                'subtotal' => 2400.00,
                'tax_amount' => 240.00,
                'total_amount' => 2640.00,
                'notes' => 'Screen printing for CLB-001 corporate logos on Kipling Q4 order.',
            ],
        ];

        foreach ($poData as $po) {
            PurchaseOrder::updateOrCreate(
                ['po_number' => $po['po_number']],
                $po
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  PROJECTS
    // ──────────────────────────────────────────────────────────────
    protected function seedProjects(): void
    {
        $so1 = SalesOrder::where('so_number', 'SO-KEI-2025-0001')->first();
        $so2 = SalesOrder::where('so_number', 'SO-KEI-2025-0002')->first();
        $so3 = SalesOrder::where('so_number', 'SO-KEI-2026-0001')->first();

        $projects = [
            // SO-0001 projects (shipped)
            [
                'company_id' => self::$keiId,
                'code' => 'PRJ-2025-0001',
                'name' => 'Vera Bradley Explorer Q3 2025',
                'description' => '2,000 Explorer Backpack Pro (EBP-001) for Vera Bradley Q3 2025 collection.',
                'type' => 'mass',
                'status' => 'completed',
                'customer_id' => $so1->customer_id,
                'sales_order_id' => $so1->id,
                'design_id' => null,
                'start_date' => '2025-03-20',
                'target_date' => '2025-06-01',
                'completed_at' => '2025-05-28',
                'target_qty' => 2000,
                'produced_qty' => 2000,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'PRJ-2025-0002',
                'name' => 'Vera Bradley Urban Handbag Q3',
                'description' => '1,200 Urban Handbag Series (UHS-001) for Vera Bradley Q3 2025.',
                'type' => 'mass',
                'status' => 'completed',
                'customer_id' => $so1->customer_id,
                'sales_order_id' => $so1->id,
                'design_id' => null,
                'start_date' => '2025-04-01',
                'target_date' => '2025-06-10',
                'completed_at' => '2025-06-08',
                'target_qty' => 1200,
                'produced_qty' => 1200,
            ],
            // SO-0002 projects (production)
            [
                'company_id' => self::$keiId,
                'code' => 'PRJ-2025-0003',
                'name' => 'Kipling Q4 2025 Explorer Backpack',
                'description' => '3,000 Explorer Backpack Pro for Kipling Q4 2025 holiday season.',
                'type' => 'mass',
                'status' => 'production',
                'customer_id' => $so2->customer_id,
                'sales_order_id' => $so2->id,
                'design_id' => null,
                'start_date' => '2025-08-01',
                'target_date' => '2025-10-10',
                'completed_at' => null,
                'target_qty' => 3000,
                'produced_qty' => 1840,
            ],
            [
                'company_id' => self::$keiId,
                'code' => 'PRJ-2025-0004',
                'name' => 'Kipling Q4 2025 Corporate Laptop Backpack',
                'description' => '2,000 Corporate Laptop Backpack (CLB-001) for Kipling Q4 2025.',
                'type' => 'mass',
                'status' => 'production',
                'customer_id' => $so2->customer_id,
                'sales_order_id' => $so2->id,
                'design_id' => null,
                'start_date' => '2025-08-05',
                'target_date' => '2025-10-15',
                'completed_at' => null,
                'target_qty' => 2000,
                'produced_qty' => 850,
            ],
            // SO-0003 project (planning)
            [
                'company_id' => self::$keiId,
                'code' => 'PRJ-2026-0001',
                'name' => 'Cotopaxi Urban Handbag Spring 2026',
                'description' => '2,000 Urban Handbag Series for Cotopaxi Spring 2026 trial collection.',
                'type' => 'sample',
                'status' => 'planning',
                'customer_id' => $so3->customer_id,
                'sales_order_id' => $so3->id,
                'design_id' => null,
                'start_date' => '2026-02-01',
                'target_date' => '2026-04-05',
                'completed_at' => null,
                'target_qty' => 2000,
                'produced_qty' => 0,
            ],
        ];

        foreach ($projects as $project) {
            Project::updateOrCreate(
                ['code' => $project['code']],
                $project
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  PROJECT BOMS
    // ──────────────────────────────────────────────────────────────
    protected function seedProjectBoms(): void
    {
        $prj1 = Project::where('code', 'PRJ-2025-0001')->first();
        $prj2 = Project::where('code', 'PRJ-2025-0002')->first();
        $prj3 = Project::where('code', 'PRJ-2025-0003')->first();

        $fab001 = Material::where('code', 'FAB-001')->first();
        $fab002 = Material::where('code', 'FAB-002')->first();
        $fab003 = Material::where('code', 'FAB-003')->first();
        $fab004 = Material::where('code', 'FAB-004')->first();
        $fab005 = Material::where('code', 'FAB-005')->first();
        $zip001 = Material::where('code', 'ZIP-001')->first();
        $zip002 = Material::where('code', 'ZIP-002')->first();
        $acc001 = Material::where('code', 'ACC-001')->first();
        $acc002 = Material::where('code', 'ACC-002')->first();
        $acc003 = Material::where('code', 'ACC-003')->first();
        $acc004 = Material::where('code', 'ACC-004')->first();
        $thr001 = Material::where('code', 'THR-001')->first();
        $int001 = Material::where('code', 'INT-001')->first();

        // BOM line builder with wastage_pct (5% default)
        $makeBomLine = fn($projectId, $materialId, $qty, $notes = null) => [
            'project_id' => $projectId,
            'material_id' => $materialId,
            'quantity' => $qty,
            'wastage_pct' => 5.0,
            'final_quantity' => round($qty * 1.05, 3),
            'notes' => $notes,
        ];

        $bomEBP = [
            $makeBomLine($prj1->id, $fab001->id, 2.5),
            $makeBomLine($prj1->id, $fab002->id, 0.8),
            $makeBomLine($prj1->id, $fab005->id, 1.5),
            $makeBomLine($prj1->id, $zip001->id, 4),
            $makeBomLine($prj1->id, $zip002->id, 2),
            $makeBomLine($prj1->id, $acc001->id, 4),
            $makeBomLine($prj1->id, $acc002->id, 2),
            $makeBomLine($prj1->id, $acc003->id, 3.5),
            $makeBomLine($prj1->id, $acc004->id, 2),
            $makeBomLine($prj1->id, $thr001->id, 1.5),
            $makeBomLine($prj1->id, $int001->id, 2),
        ];

        $bomUHS = [
            $makeBomLine($prj2->id, $fab003->id, 2.2),
            $makeBomLine($prj2->id, $fab004->id, 0.5),
            $makeBomLine($prj2->id, $fab005->id, 1.0),
            $makeBomLine($prj2->id, $zip001->id, 3),
            $makeBomLine($prj2->id, $acc001->id, 2),
            $makeBomLine($prj2->id, $acc003->id, 2.0),
            $makeBomLine($prj2->id, $thr001->id, 1.0),
        ];

        $allBom = array_merge($bomEBP, $bomUHS);

        // Copy EBP BOM to PRJ-2025-0003 too
        foreach ($bomEBP as $bomLine) {
            $line = $bomLine;
            $line['project_id'] = $prj3->id;
            $allBom[] = $line;
        }

        foreach ($allBom as $bom) {
            ProjectBom::updateOrCreate(
                ['project_id' => $bom['project_id'], 'material_id' => $bom['material_id']],
                $bom
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  SHIPMENTS
    // ──────────────────────────────────────────────────────────────
    protected function seedShipments(): void
    {
        $so1 = SalesOrder::where('so_number', 'SO-KEI-2025-0001')->first();

        $shipments = [
            [
                'company_id' => self::$keiId,
                'shipment_number' => 'SHP-KEI-2025-0001',
                'sales_order_id' => $so1->id,
                'shipment_date' => '2025-06-01',
                'status' => 'in_transit',
                'shipping_method' => 'sea',
                'container_number' => 'TEMU3827461',
                'bl_number' => 'HLC-2025060101',
                'carrier' => 'Evergreen Marine',
                'port_of_loading' => 'Tanjung Priok, Jakarta',
                'port_of_discharge' => 'Port of Los Angeles, CA',
                'etd' => '2025-06-01',
                'eta' => '2025-07-10',
                'total_packages' => 420,
                'total_gross_weight_kg' => 3640.00,
                'total_volume_m3' => 28.50,
                'shipping_cost_usd' => 3200.00,
                'notes' => '1x 40\'HC container. All EBP-001 units (2,000 pcs). FCL shipment.',
            ],
            [
                'company_id' => self::$keiId,
                'shipment_number' => 'SHP-KEI-2025-0002',
                'sales_order_id' => $so1->id,
                'shipment_date' => '2025-06-05',
                'status' => 'in_transit',
                'shipping_method' => 'sea',
                'container_number' => null,
                'bl_number' => 'MAERSK-2025060502',
                'carrier' => 'Maersk Line',
                'port_of_loading' => 'Tanjung Priok, Jakarta',
                'port_of_discharge' => 'Port of Vancouver, BC',
                'etd' => '2025-06-05',
                'eta' => '2025-07-20',
                'total_packages' => 210,
                'total_gross_weight_kg' => 1680.00,
                'total_volume_m3' => 12.40,
                'shipping_cost_usd' => 1850.00,
                'notes' => 'LCL shipment. UHS-001 units (1,200 pcs). Consolidated with other shipper.',
            ],
        ];

        foreach ($shipments as $shipment) {
            Shipment::updateOrCreate(
                ['shipment_number' => $shipment['shipment_number']],
                $shipment
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  INVOICES
    // ──────────────────────────────────────────────────────────────
    protected function seedInvoices(): void
    {
        $so1 = SalesOrder::where('so_number', 'SO-KEI-2025-0001')->first();
        $so2 = SalesOrder::where('so_number', 'SO-KEI-2025-0002')->first();
        $po1 = PurchaseOrder::where('po_number', 'PO-KEI-2025-0001')->first();

        $ebp = Product::where('code', 'EBP-001')->first();
        $uhs = Product::where('code', 'UHS-001')->first();

        $ykk = Supplier::where('code', 'SUP-001')->first();
        $veraBradley = Customer::where('code', 'CUS-001')->first();
        $kipling = Customer::where('code', 'CUS-002')->first();

        $invoices = [
            // Sales Invoice 1 — Vera Bradley SO-0001
            [
                'company_id' => self::$keiId,
                'invoice_number' => 'INV-KEI-2025-0001',
                'type' => 'sales',
                'sales_order_id' => $so1->id,
                'purchase_order_id' => null,
                'customer_id' => $veraBradley->id,
                'supplier_id' => null,
                'invoice_date' => '2025-06-02',
                'due_date' => '2025-08-01',
                'status' => 'sent',
                'currency' => 'USD',
                'subtotal' => 45600.00,
                'tax_pct' => 0.00,
                'tax_amount' => 0.00,
                'shipping_cost' => 3200.00,
                'total_amount' => 48800.00,
                'notes' => 'Commercial invoice for SO-KEI-2025-0001. Payment due 60 days.',
                'is_tax_invoice' => false,
                'tax_invoice_number' => null,
            ],
            // Sales Invoice 2 — Kipling SO-0002 (partial billing)
            [
                'company_id' => self::$keiId,
                'invoice_number' => 'INV-KEI-2025-0002',
                'type' => 'sales',
                'sales_order_id' => $so2->id,
                'purchase_order_id' => null,
                'customer_id' => $kipling->id,
                'supplier_id' => null,
                'invoice_date' => '2025-10-15',
                'due_date' => '2025-11-29',
                'status' => 'sent',
                'currency' => 'USD',
                'subtotal' => 68400.00,
                'tax_pct' => 0.00,
                'tax_amount' => 0.00,
                'shipping_cost' => 4800.00,
                'total_amount' => 73200.00,
                'notes' => 'Commercial invoice for SO-KEI-2025-0002. Net 45 days.',
                'is_tax_invoice' => false,
                'tax_invoice_number' => null,
            ],
        ];

        foreach ($invoices as $invoiceData) {
            $invoice = Invoice::updateOrCreate(
                ['invoice_number' => $invoiceData['invoice_number']],
                $invoiceData
            );

            // Seed invoice line items
            if ($invoiceData['invoice_number'] === 'INV-KEI-2025-0001') {
                InvoiceItem::updateOrCreate(
                    ['invoice_id' => $invoice->id, 'product_id' => $ebp->id],
                    [
                        'invoice_id' => $invoice->id,
                        'product_id' => $ebp->id,
                        'material_id' => null,
                        'description' => 'Explorer Backpack Pro (EBP-001) — 2,000 pcs',
                        'quantity' => 2000,
                        'unit' => 'pcs',
                        'unit_price' => 16.80,
                        'total_price' => 33600.00,
                    ]
                );
                InvoiceItem::updateOrCreate(
                    ['invoice_id' => $invoice->id, 'product_id' => $uhs->id],
                    [
                        'invoice_id' => $invoice->id,
                        'product_id' => $uhs->id,
                        'material_id' => null,
                        'description' => 'Urban Handbag Series (UHS-001) — 1,200 pcs',
                        'quantity' => 1200,
                        'unit' => 'pcs',
                        'unit_price' => 10.00,
                        'total_price' => 12000.00,
                    ]
                );
            }
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  GOODS RECEIPTS
    // ──────────────────────────────────────────────────────────────
    protected function seedGoodsReceipts(): void
    {
        $ykk = Supplier::where('code', 'SUP-001')->first();
        $duraflex = Supplier::where('code', 'SUP-003')->first();

        if ($ykk) {
            GoodsReceipt::updateOrCreate(
                ['gr_number' => 'GR-KEI-2025-0001'],
                [
                    'company_id' => self::$keiId,
                    'gr_number' => 'GR-KEI-2025-0001',
                    'purchase_receipt_id' => null,
                    'supplier_id' => $ykk->id,
                    'receipt_date' => '2025-04-26',
                    'invoice_number' => 'YKK-INV-2025-0412',
                    'notes' => 'Full delivery: YKK #5 zippers 3,000 pcs, YKK #8 zippers 2,000 pcs.',
                    'received_by' => 'Warehouse Team',
                ]
            );
        }

        if ($duraflex) {
            GoodsReceipt::updateOrCreate(
                ['gr_number' => 'GR-KEI-2025-0002'],
                [
                    'company_id' => self::$keiId,
                    'gr_number' => 'GR-KEI-2025-0002',
                    'purchase_receipt_id' => null,
                    'supplier_id' => $duraflex->id,
                    'receipt_date' => '2025-04-29',
                    'invoice_number' => 'DFX-INV-2025-0089',
                    'notes' => 'Full delivery: D-rings, buckles, and webbing per PO-KEI-2025-0002.',
                    'received_by' => 'Warehouse Team',
                ]
            );
        }
    }
}