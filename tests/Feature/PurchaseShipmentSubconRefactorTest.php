<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PoSupplier;
use App\Models\PoSubcon;
use App\Models\PurchaseShipment;
use App\Models\Subcon;
use App\Models\SubconMaterialOut;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseShipmentSubconRefactorTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Supplier $supplier;
    private Subcon $subcon;
    private PoSupplier $poSupplier;
    private PoSubcon $poSubcon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'PT Komitrando Emporio Test',
            'code' => 'KOMI-TEST',
            'address' => 'Jogja',
        ]);

        $this->supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Test Supplier',
            'code' => 'SUP-TEST',
        ]);

        $this->subcon = Subcon::create([
            'company_id' => $this->company->id,
            'name' => 'Test Subcon',
            'code' => 'SUB-TEST',
            'service_type' => 'sewing',
        ]);

        $this->poSupplier = PoSupplier::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'POSUP-001',
            'po_date' => now(),
            'status' => 'ordered',
        ]);

        $this->poSubcon = PoSubcon::create([
            'company_id' => $this->company->id,
            'subcon_id' => $this->subcon->id,
            'po_number' => 'POSUB-001',
            'po_date' => now(),
            'status' => 'ordered',
        ]);
    }

    public function test_purchase_shipment_forces_supplier_po_type(): void
    {
        $shipment = PurchaseShipment::create([
            'company_id' => $this->company->id,
            'shipment_number' => 'SHIP-001',
            'po_type' => 'subcon', // attempt to set subcon
            'po_id' => $this->poSupplier->id,
            'shipment_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->assertEquals('supplier', $shipment->po_type);
        $this->assertDatabaseHas('purchase_shipments', [
            'id' => $shipment->id,
            'po_type' => 'supplier',
        ]);
    }

    public function test_subcon_material_out_saves_delivery_fields(): void
    {
        $out = SubconMaterialOut::create([
            'company_id' => $this->company->id,
            'po_subcon_id' => $this->poSubcon->id,
            'subcon_id' => $this->subcon->id,
            'document_number' => 'SMO-001',
            'departure_date' => now()->toDateString(),
            'status' => 'draft',
            'delivery_method' => 'fleet',
            'courier_name' => 'Driver PT Komitrando',
            'delivery_cost' => 50000.00,
            'estimated_arrival' => '2026-08-05',
        ]);

        $this->assertEquals('fleet', $out->delivery_method);
        $this->assertEquals('Driver PT Komitrando', $out->courier_name);
        $this->assertEquals(50000.00, $out->delivery_cost);
        $this->assertEquals('2026-08-05', $out->estimated_arrival->format('Y-m-d'));
    }

    public function test_subcon_material_out_status_received(): void
    {
        $out = SubconMaterialOut::create([
            'company_id' => $this->company->id,
            'po_subcon_id' => $this->poSubcon->id,
            'subcon_id' => $this->subcon->id,
            'document_number' => 'SMO-002',
            'departure_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $out->update(['status' => 'received']);

        $this->assertEquals('received', $out->fresh()->status);
        $this->assertDatabaseHas('subcon_material_outs', [
            'id' => $out->id,
            'status' => 'received',
        ]);
    }
}
