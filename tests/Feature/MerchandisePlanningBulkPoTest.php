<?php

namespace Tests\Feature;

use App\Filament\Resources\MerchandisePlanningResource\Pages\ListMerchandisePlannings;
use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\PoSubcon;
use App\Models\PoSupplier;
use App\Models\Project;
use App\Models\Subcon;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\CompanyContext;
use App\Services\PoGenerationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MerchandisePlanningBulkPoTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Supplier $supplier;

    private Subcon $subcon;

    private Material $material;

    private Project $projectA;

    private Project $projectB;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'PT Komitrando Emporio Test',
            'code' => 'KOMI-TEST',
            'address' => 'Jogja',
        ]);

        CompanyContext::setCompany($this->company);

        $this->supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Supplier Textile Corp',
            'code' => 'SUP-TEX',
            'email' => 'john@textile.com',
            'phone' => '1111111',
            'address' => 'Bandung',
        ]);

        $this->subcon = Subcon::create([
            'company_id' => $this->company->id,
            'name' => 'Sewing Subcon Jogja',
            'code' => 'SUB-SEW',
            'service_type' => 'sewing',
            'email' => 'slamet@sewing.com',
            'phone' => '2222222',
            'address' => 'Sleman',
        ]);

        $this->material = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-NYLON-01',
            'name' => 'Nylon Fabric Navy',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 10000,
            'stock' => 0,
        ]);

        $this->projectA = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-2026-001',
            'name' => 'Project Alpha',
            'type' => 'mass',
            'status' => 'planning',
        ]);

        $this->projectB = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-2026-002',
            'name' => 'Project Beta',
            'type' => 'sample',
            'status' => 'planning',
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'code' => 'WH-MAIN',
            'name' => 'Main Warehouse',
            'address' => 'Bantul',
            'is_active' => true,
        ]);
    }

    public function test_bulk_generate_po_combines_multiple_projects_into_single_supplier_po(): void
    {
        $planningA = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectA->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 100000,
            'total_subcon_cost' => 0,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningA->id,
            'material_id' => $this->material->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 10.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 100000.00,
            'is_subcon' => false,
            'component' => 'Body Panel',
        ]);

        $planningB = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectB->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 150000,
            'total_subcon_cost' => 0,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningB->id,
            'material_id' => $this->material->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 15.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 150000.00,
            'is_subcon' => false,
            'component' => 'Side Pocket',
        ]);

        $service = app(PoGenerationService::class);
        $result = $service->generateFromPlannings(collect([$planningA, $planningB]));

        $this->assertCount(1, $result['po_numbers']);

        $poSupplier = PoSupplier::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($poSupplier);

        // Assert project_ids contains both projects
        $this->assertContains($this->projectA->id, $poSupplier->project_ids);
        $this->assertContains($this->projectB->id, $poSupplier->project_ids);

        // Subtotal = 10*10,000 + 15*10,000 = 250,000; PPN 11% = 27,500; Grand Total = 277,500
        $this->assertEquals(250000.00, (float) $poSupplier->subtotal);
        $this->assertEquals(27500.00, (float) $poSupplier->ppn_amount);
        $this->assertEquals(277500.00, (float) $poSupplier->grand_total);

        // Assert items contain project tracking
        $items = $poSupplier->items;
        $this->assertCount(2, $items);

        $itemA = $items->firstWhere('project_id', $this->projectA->id);
        $this->assertNotNull($itemA);
        $this->assertEquals(10.00, (float) $itemA->qty);
        $this->assertEquals('Body Panel', $itemA->component);

        $itemB = $items->firstWhere('project_id', $this->projectB->id);
        $this->assertNotNull($itemB);
        $this->assertEquals(15.00, (float) $itemB->qty);
        $this->assertEquals('Side Pocket', $itemB->component);
    }

    public function test_bulk_generate_po_allocates_stock_fifo_across_projects(): void
    {
        $planningA = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectA->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 100000,
            'total_subcon_cost' => 0,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningA->id,
            'material_id' => $this->material->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 10.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 100000.00,
            'is_subcon' => false,
        ]);

        $planningB = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectB->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 150000,
            'total_subcon_cost' => 0,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningB->id,
            'material_id' => $this->material->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 15.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 150000.00,
            'is_subcon' => false,
        ]);

        // Existing stock: 12 yards
        // Planning A takes 10 yards -> Shortage: 0 (No PO item for Project A)
        // Planning B gets remaining 2 yards -> Shortage: 13 yards (PO item for Project B with qty 13)
        InventoryStock::create([
            'company_id' => $this->company->id,
            'material_id' => $this->material->id,
            'quantity' => 12.00,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $service = app(PoGenerationService::class);
        $result = $service->generateFromPlannings(collect([$planningA, $planningB]));

        $poSupplier = PoSupplier::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($poSupplier);

        $items = $poSupplier->items;
        $this->assertCount(1, $items);

        $item = $items->first();
        $this->assertEquals($this->projectB->id, $item->project_id);
        $this->assertEquals(13.00, (float) $item->qty);
        $this->assertEquals(130000.00, (float) $item->total_price);
    }

    public function test_bulk_generate_po_merges_into_existing_draft_po(): void
    {
        $projectC = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-2026-003',
            'name' => 'Project Charlie',
            'type' => 'mass',
            'status' => 'planning',
        ]);

        // Pre-existing draft PO
        $existingPo = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-SUP-2026-999',
            'supplier_id' => $this->supplier->id,
            'project_id' => $projectC->id,
            'project_ids' => [$projectC->id],
            'po_date' => now()->toDateString(),
            'ppn_percent' => 11,
            'status' => 'draft',
            'subtotal' => 0,
            'ppn_amount' => 0,
            'grand_total' => 0,
        ]);

        $planningA = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectA->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 100000,
            'total_subcon_cost' => 0,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningA->id,
            'material_id' => $this->material->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 5.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 50000.00,
            'is_subcon' => false,
        ]);

        $service = app(PoGenerationService::class);
        $result = $service->generateFromPlannings(collect([$planningA]));

        $this->assertContains('PO-SUP-2026-999 (updated)', $result['po_numbers']);

        $existingPo->refresh();
        $this->assertContains($projectC->id, $existingPo->project_ids);
        $this->assertContains($this->projectA->id, $existingPo->project_ids);
        $this->assertEquals(50000.00, (float) $existingPo->subtotal);
    }

    public function test_bulk_generate_po_for_subcons_across_projects(): void
    {
        $planningA = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectA->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 0,
            'total_subcon_cost' => 50000,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningA->id,
            'subcon_id' => $this->subcon->id,
            'planned_qty' => 10.00,
            'unit' => 'pcs',
            'unit_price' => 5000.00,
            'total_price' => 50000.00,
            'is_subcon' => true,
            'component' => 'Sewing Body',
        ]);

        $planningB = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectB->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 0,
            'total_subcon_cost' => 75000,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningB->id,
            'subcon_id' => $this->subcon->id,
            'planned_qty' => 15.00,
            'unit' => 'pcs',
            'unit_price' => 5000.00,
            'total_price' => 75000.00,
            'is_subcon' => true,
            'component' => 'Sewing Strap',
        ]);

        $service = app(PoGenerationService::class);
        $result = $service->generateFromPlannings(collect([$planningA, $planningB]));

        $poSubcon = PoSubcon::where('subcon_id', $this->subcon->id)->first();
        $this->assertNotNull($poSubcon);
        $this->assertContains($this->projectA->id, $poSubcon->project_ids);
        $this->assertContains($this->projectB->id, $poSubcon->project_ids);

        $this->assertEquals(125000.00, (float) $poSubcon->service_cost);
        $this->assertEquals(125000.00, (float) $poSubcon->total_cost);
        $this->assertCount(2, $poSubcon->items);
    }

    public function test_filament_table_bulk_action_skips_non_finalised_plannings(): void
    {
        $planningFinalised = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectA->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 50000,
            'total_subcon_cost' => 0,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningFinalised->id,
            'material_id' => $this->material->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 5.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 50000.00,
            'is_subcon' => false,
        ]);

        $planningPreliminary = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectB->id,
            'planning_date' => now()->toDateString(),
            'status' => 'preliminary',
            'total_material_cost' => 100000,
            'total_subcon_cost' => 0,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningPreliminary->id,
            'material_id' => $this->material->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 10.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 100000.00,
            'is_subcon' => false,
        ]);

        // Call bulk action via Livewire
        Livewire::test(ListMerchandisePlannings::class)
            ->callTableBulkAction('bulkGeneratePO', [$planningFinalised, $planningPreliminary]);

        $poSupplier = PoSupplier::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($poSupplier);

        // Only Project A should be generated
        $this->assertEquals([$this->projectA->id], $poSupplier->project_ids);
        $this->assertCount(1, $poSupplier->items);
        $this->assertEquals(5.00, (float) $poSupplier->items->first()->qty);
    }

    public function test_bulk_generate_po_confirmation_modal_renders_detailed_table_for_multiple_projects(): void
    {
        $planningA = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectA->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 100000,
            'total_subcon_cost' => 50000,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningA->id,
            'material_id' => $this->material->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 10.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 100000.00,
            'is_subcon' => false,
            'component' => 'Body Panel',
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningA->id,
            'subcon_id' => $this->subcon->id,
            'planned_qty' => 5.00,
            'unit' => 'pcs',
            'unit_price' => 10000.00,
            'total_price' => 50000.00,
            'is_subcon' => true,
            'notes' => 'Sewing Panel Alpha',
        ]);

        $planningB = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->projectB->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 150000,
            'total_subcon_cost' => 0,
        ]);

        MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planningB->id,
            'material_id' => $this->material->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 15.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 150000.00,
            'is_subcon' => false,
            'component' => 'Side Pocket',
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'material_id' => $this->material->id,
            'quantity' => 2.00,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $plannings = new Collection([$planningA, $planningB]);
        $plannings->load(['items.material', 'items.supplier', 'items.subcon', 'project', 'subProject']);

        $view = $this->view('filament.components.generate-po-modal', [
            'records' => $plannings,
            'stocks' => [$this->material->id => 2.00],
        ]);

        // Assert Modal Header & Intro
        $view->assertSee('Generating consolidated POs from');
        $view->assertSee('2'); // 2 finalised plannings
        $view->assertSee('Supplier Purchase Orders');
        $view->assertSee('Supplier Textile Corp');

        // Assert Project Tags for both projects
        $view->assertSee('Project Alpha');
        $view->assertSee('PRJ-2026-001');
        $view->assertSee('Project Beta');
        $view->assertSee('PRJ-2026-002');

        // Assert Subcon Section
        $view->assertSee('Subcontractor Services');
        $view->assertSee('Sewing Subcon Jogja');
        $view->assertSee('Sewing Panel Alpha');
    }
}
