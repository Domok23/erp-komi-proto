<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PoSupplier;
use App\Models\PoSubcon;
use App\Models\PurchaseShipment;
use App\Models\Supplier;
use App\Models\Subcon;
use App\Models\User;
use App\Models\DeliveryAlertLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DeliveryAlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Supplier $supplier;
    private Subcon $subcon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Alert Test Company',
            'code' => 'ALRT01',
        ]);

        $this->user = User::create([
            'name' => 'Alert Admin',
            'email' => 'alertadmin@test.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);

        $this->supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Supplier Alrt',
            'code' => 'SUPALRT',
        ]);

        $this->subcon = Subcon::create([
            'company_id' => $this->company->id,
            'name' => 'Subcon Alrt',
            'code' => 'SUBCALRT',
            'service_type' => 'sewing',
        ]);
    }

    public function test_po_without_deadline_is_skipped()
    {
        // PO with null delivery_date and no shipments
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-SKIP',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'delivery_date' => null,
            'status' => 'ordered',
        ]);

        $this->artisan('app:send-delivery-alerts');

        $this->assertEquals(0, DeliveryAlertLog::count());
    }

    public function test_po_with_delivery_date_alerts_correctly()
    {
        // PO with delivery date 2 days in future (within H-3 to H-1 warning)
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-WARNING',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'delivery_date' => Carbon::today()->addDays(2),
            'status' => 'ordered',
        ]);

        $this->artisan('app:send-delivery-alerts');

        $this->assertEquals(1, DeliveryAlertLog::count());
        $log = DeliveryAlertLog::first();
        $this->assertEquals('warning', $log->alert_level);
        $this->assertEquals(-2, $log->days_overdue);

        // Verify notification was sent to database
        $this->assertEquals(1, $this->user->notifications()->count());
    }

    public function test_active_shipment_priority_over_po_delivery_date()
    {
        // PO delivery date is H-5 (no warning), but shipment ETA is H-2 (warning)
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-SHIPMENT-PRIORITY',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'delivery_date' => Carbon::today()->addDays(5),
            'status' => 'ordered',
        ]);

        // Shipment with ETA H-2 (active status transit)
        $po->purchaseShipments()->create([
            'company_id' => $this->company->id,
            'shipment_number' => 'SHIP-01',
            'po_type' => 'supplier',
            'status' => 'in_transit',
            'shipment_date' => now(),
            'eta' => Carbon::today()->addDays(2),
        ]);

        // Shipment with ETA H+4 (but cancelled, so ignored)
        $po->purchaseShipments()->create([
            'company_id' => $this->company->id,
            'shipment_number' => 'SHIP-02',
            'po_type' => 'supplier',
            'status' => 'cancelled',
            'shipment_date' => now(),
            'eta' => Carbon::today()->subDays(4),
        ]);

        $this->artisan('app:send-delivery-alerts');

        $this->assertEquals(1, DeliveryAlertLog::count());
        $log = DeliveryAlertLog::first();
        $this->assertEquals('warning', $log->alert_level);
        $this->assertEquals(-2, $log->days_overdue); // calculated against shipment ETA (2 days in future)
    }

    public function test_backfills_missed_lower_alerts()
    {
        // PO is late by 8 days, never alert logged before
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-CRITICAL',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'delivery_date' => Carbon::today()->subDays(8),
            'status' => 'ordered',
        ]);

        $this->artisan('app:send-delivery-alerts');

        // Should log warning, overdue, and escalation (3 logs)
        $this->assertEquals(3, DeliveryAlertLog::count());
        $this->assertEquals(1, DeliveryAlertLog::where('alert_level', 'warning')->count());
        $this->assertEquals(1, DeliveryAlertLog::where('alert_level', 'overdue')->count());
        $this->assertEquals(1, DeliveryAlertLog::where('alert_level', 'escalation')->count());

        // Users should receive 3 notifications
        $this->assertEquals(3, $this->user->notifications()->count());
    }

    public function test_deduplication_prevents_multiple_alerts()
    {
        // PO is due today
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-DEDUP',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'delivery_date' => Carbon::today(),
            'status' => 'ordered',
        ]);

        // Run first time (warning and overdue triggered since it jumps to H+0)
        $this->artisan('app:send-delivery-alerts');
        $this->assertEquals(2, DeliveryAlertLog::count());
        $this->assertEquals(2, $this->user->notifications()->count());

        // Run second time (nothing should be sent)
        $this->artisan('app:send-delivery-alerts');
        $this->assertEquals(2, DeliveryAlertLog::count());
        $this->assertEquals(2, $this->user->notifications()->count());
    }

    public function test_po_subcon_alerting()
    {
        $po = PoSubcon::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-SUBCON',
            'subcon_id' => $this->subcon->id,
            'po_date' => now(),
            'delivery_date' => Carbon::today()->addDays(1), // H-1 warning
            'status' => 'ordered',
        ]);

        $this->artisan('app:send-delivery-alerts');

        $this->assertEquals(1, DeliveryAlertLog::count());
        $this->assertEquals('warning', DeliveryAlertLog::first()->alert_level);
    }
}
