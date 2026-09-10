<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DeliveryAlertLog;
use App\Models\Material;
use App\Models\PoSubcon;
use App\Models\PoSubconItem;
use App\Models\PoSupplier;
use App\Models\PoSupplierItem;
use App\Models\PurchaseShipment;
use App\Models\Subcon;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DeliveryAlertSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create(['name' => 'Komi ERP Default', 'code' => 'KED']);
        $supplierAccessories = Supplier::where('code', 'SUP-001')->where('company_id', $company->id)->first()
            ?? Supplier::first()
            ?? Supplier::create(['company_id' => $company->id, 'name' => 'PT Aksesoris Utama', 'code' => 'SUP-001']);

        $supplierThread = Supplier::where('code', 'SUP-004')->where('company_id', $company->id)->first()
            ?? $supplierAccessories;

        $supplierFabric = Supplier::where('code', 'SUP-003')->where('company_id', $company->id)->first()
            ?? $supplierAccessories;

        $subcon = Subcon::first() ?? Subcon::create(['company_id' => $company->id, 'name' => 'Default Subcon', 'code' => 'SUBC001', 'service_type' => 'assembly']);

        $matZipper = Material::where('code', 'ZIP-001')->first();
        $matWebbing = Material::where('code', 'ACC-003')->first();
        $matFabric = Material::where('code', 'FAB-001')->first();

        // Cleanup old test and demo alert records if re-running
        PurchaseShipment::whereIn('shipment_number', ['SHIP-SUB-TEST-01', 'SHIP-SUB-2026-001'])->delete();
        PoSupplierItem::whereHas('poSupplier', function ($q) {
            $q->whereIn('po_number', ['PO-SUP-WARNING-TEST', 'PO-SUP-OVERDUE-TEST', 'PO-SUP-ESCALATE-TEST', 'PO-SUP-2026-002', 'PO-SUP-2026-003', 'PO-SUP-2026-004']);
        })->delete();
        PoSupplier::whereIn('po_number', ['PO-SUP-WARNING-TEST', 'PO-SUP-OVERDUE-TEST', 'PO-SUP-ESCALATE-TEST', 'PO-SUP-2026-002', 'PO-SUP-2026-003', 'PO-SUP-2026-004'])->delete();

        PoSubconItem::whereHas('poSubcon', function ($q) {
            $q->whereIn('po_number', ['PO-SUB-SHIP-TEST', 'PO-SUB-2026-002']);
        })->delete();
        PoSubcon::whereIn('po_number', ['PO-SUB-SHIP-TEST', 'PO-SUB-2026-002'])->delete();

        DB::table('notifications')->delete();
        DeliveryAlertLog::truncate();

        // 1. PO Supplier: Warning Alert (due in 2 days) & Pending Approval for Executive E-Sign demo
        $poWarning = PoSupplier::create([
            'company_id' => $company->id,
            'po_number' => 'PO-SUP-2026-002',
            'supplier_id' => $supplierAccessories->id,
            'po_date' => Carbon::today()->subDays(5),
            'delivery_date' => Carbon::today()->addDays(2),
            'status' => 'ordered',
            'approval_status' => 'pending_approval',
            'subtotal' => 4550000,
            'ppn_percent' => 10,
            'ppn_amount' => 455000,
            'grand_total' => 5005000,
            'notes' => 'Urgent zipper & accessory order for production batch 2',
        ]);

        if ($matZipper) {
            PoSupplierItem::create([
                'po_supplier_id' => $poWarning->id,
                'material_id' => $matZipper->id,
                'description' => 'YKK Zipper #5 Black',
                'qty' => 500,
                'unit' => 'pcs',
                'unit_price' => 7500,
                'total_price' => 3750000,
                'qty_received' => 0,
            ]);
        }
        if ($matWebbing) {
            PoSupplierItem::create([
                'po_supplier_id' => $poWarning->id,
                'material_id' => $matWebbing->id,
                'description' => 'Webbing Nylon 25mm Black',
                'qty' => 200,
                'unit' => 'm',
                'unit_price' => 4000,
                'total_price' => 800000,
                'qty_received' => 0,
            ]);
        }

        // 2. PO Supplier: Overdue Alert (due 1 day ago)
        $poOverdue = PoSupplier::create([
            'company_id' => $company->id,
            'po_number' => 'PO-SUP-2026-003',
            'supplier_id' => $supplierThread->id,
            'po_date' => Carbon::today()->subDays(5),
            'delivery_date' => Carbon::today()->subDays(1),
            'status' => 'ordered',
            'approval_status' => 'approved',
            'subtotal' => 6000000,
            'ppn_percent' => 10,
            'ppn_amount' => 600000,
            'grand_total' => 6600000,
            'notes' => 'Sewing thread supply delayed in transit from supplier warehouse',
        ]);

        if ($matWebbing) {
            PoSupplierItem::create([
                'po_supplier_id' => $poOverdue->id,
                'material_id' => $matWebbing->id,
                'description' => 'Webbing Heavy Duty 38mm',
                'qty' => 1500,
                'unit' => 'm',
                'unit_price' => 4000,
                'total_price' => 6000000,
                'qty_received' => 0,
            ]);
        }

        // 3. PO Supplier: Escalation Alert (due 9 days ago)
        $poEscalate = PoSupplier::create([
            'company_id' => $company->id,
            'po_number' => 'PO-SUP-2026-004',
            'supplier_id' => $supplierFabric->id,
            'po_date' => Carbon::today()->subDays(15),
            'delivery_date' => Carbon::today()->subDays(9),
            'status' => 'ordered',
            'approval_status' => 'approved',
            'subtotal' => 9000000,
            'ppn_percent' => 10,
            'ppn_amount' => 900000,
            'grand_total' => 9900000,
            'notes' => 'Escalated fabric backorder awaiting customs clearance',
        ]);

        if ($matFabric) {
            PoSupplierItem::create([
                'po_supplier_id' => $poEscalate->id,
                'material_id' => $matFabric->id,
                'description' => 'Cordura 1000D Black Fabric',
                'qty' => 200,
                'unit' => 'yard',
                'unit_price' => 45000,
                'total_price' => 9000000,
                'qty_received' => 0,
            ]);
        }

        // 4. PO Subcon: Warning Alert via Shipment ETA (delivery_date next week, shipment ETA in 2 days)
        $subconPO = PoSubcon::create([
            'company_id' => $company->id,
            'po_number' => 'PO-SUB-2026-002',
            'subcon_id' => $subcon->id,
            'po_date' => Carbon::today()->subDays(5),
            'delivery_date' => Carbon::today()->addDays(10),
            'status' => 'ordered',
            'total_cost' => 2000000,
            'notes' => 'Embroidery subcontracting batch for Nike backpacks',
        ]);

        PoSubconItem::create([
            'po_subcon_id' => $subconPO->id,
            'description' => 'Bordir Logo Dada & Samping',
            'qty' => 1000,
            'unit_price' => 2000,
            'total_price' => 2000000,
        ]);

        $subconPO->purchaseShipments()->create([
            'company_id' => $company->id,
            'shipment_number' => 'SHIP-SUB-2026-001',
            'po_type' => 'subcon',
            'status' => 'in_transit',
            'shipment_date' => Carbon::today()->subDays(2),
            'eta' => Carbon::today()->addDays(2),
            'carrier' => 'JNE Cargo Express',
            'tracking_number' => 'JNE-8821940192',
            'notes' => 'Subcon parts en route to factory',
        ]);

        // Trigger alert scan so notifications and DeliveryAlertLog are immediately available
        Artisan::call('app:send-delivery-alerts');
    }
}
