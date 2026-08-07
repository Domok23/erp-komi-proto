<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PoSupplier;
use App\Models\PoSubcon;
use App\Models\Supplier;
use App\Models\Subcon;
use App\Models\PurchaseShipment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DeliveryAlertSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create(['name' => 'Komi ERP Default', 'code' => 'KED']);
        $supplier = Supplier::first() ?? Supplier::create(['company_id' => $company->id, 'name' => 'Default Supplier', 'code' => 'SUP001']);
        $subcon = Subcon::first() ?? Subcon::create(['company_id' => $company->id, 'name' => 'Default Subcon', 'code' => 'SUBC001', 'service_type' => 'assembly']);

        // Cleanup old test data & logs if re-running
        PurchaseShipment::where('shipment_number', 'SHIP-SUB-TEST-01')->delete();
        PoSupplier::whereIn('po_number', ['PO-SUP-WARNING-TEST', 'PO-SUP-OVERDUE-TEST', 'PO-SUP-ESCALATE-TEST'])->delete();
        PoSubcon::whereIn('po_number', ['PO-SUB-SHIP-TEST'])->delete();
        \Illuminate\Support\Facades\DB::table('notifications')->delete();
        \App\Models\DeliveryAlertLog::truncate();

        // 1. PO Supplier: Warning Alert (due in 2 days)
        PoSupplier::create([
            'company_id' => $company->id,
            'po_number' => 'PO-SUP-WARNING-TEST',
            'supplier_id' => $supplier->id,
            'po_date' => Carbon::today()->subDays(5),
            'delivery_date' => Carbon::today()->addDays(2),
            'status' => 'ordered',
            'grand_total' => 5000000,
        ]);

        // 2. PO Supplier: Overdue Alert (due 1 day ago)
        PoSupplier::create([
            'company_id' => $company->id,
            'po_number' => 'PO-SUP-OVERDUE-TEST',
            'supplier_id' => $supplier->id,
            'po_date' => Carbon::today()->subDays(5),
            'delivery_date' => Carbon::today()->subDays(1),
            'status' => 'ordered',
            'grand_total' => 7500000,
        ]);

        // 3. PO Supplier: Escalation Alert (due 9 days ago)
        PoSupplier::create([
            'company_id' => $company->id,
            'po_number' => 'PO-SUP-ESCALATE-TEST',
            'supplier_id' => $supplier->id,
            'po_date' => Carbon::today()->subDays(15),
            'delivery_date' => Carbon::today()->subDays(9),
            'status' => 'ordered',
            'grand_total' => 12000000,
        ]);

        // 4. PO Subcon: Warning Alert via Shipment ETA (delivery_date is next week, but shipment ETA is in 2 days)
        $subconPO = PoSubcon::create([
            'company_id' => $company->id,
            'po_number' => 'PO-SUB-SHIP-TEST',
            'subcon_id' => $subcon->id,
            'po_date' => Carbon::today()->subDays(5),
            'delivery_date' => Carbon::today()->addDays(10),
            'status' => 'ordered',
            'total_cost' => 2000000,
        ]);

        $subconPO->purchaseShipments()->create([
            'company_id' => $company->id,
            'shipment_number' => 'SHIP-SUB-TEST-01',
            'po_type' => 'subcon',
            'status' => 'in_transit',
            'shipment_date' => Carbon::today()->subDays(2),
            'eta' => Carbon::today()->addDays(2), // 2 days left
        ]);
    }
}
