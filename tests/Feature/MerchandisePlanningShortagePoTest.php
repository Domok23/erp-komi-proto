<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\PoSubcon;
use App\Models\PoSupplier;
use App\Models\Project;
use App\Models\RdDesign;
use App\Models\Subcon;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchandisePlanningShortagePoTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Supplier $supplier;
    private Subcon $subcon;
    private Material $materialInShortage;
    private Material $materialSkipped;
    private Project $project;
    private RdDesign $design;
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

        $this->materialInShortage = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-BLUE-01',
            'name' => 'Nylon Fabric Blue',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 10000,
            'stock' => 0,
        ]);

        $this->materialSkipped = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-RED-02',
            'name' => 'Cotton Fabric Red',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 12000,
            'stock' => 0,
        ]);

        $this->design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'DSN-TEST',
            'name' => 'Test Design',
            'bag_type' => 'backpack',
            'status' => 'approved',
        ]);

        $this->project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-TEST',
            'name' => 'Test Project',
            'type' => 'mass',
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

    /**
     * Test that the generate PO modal content renders correctly with stocks, shortages, and skipped warnings.
     */
    public function test_generate_po_confirmation_modal_renders_correctly(): void
    {
        // 1. Create a finalized merchandise planning
        $planning = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'design_id' => $this->design->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 100000,
            'total_subcon_cost' => 5000,
        ]);

        // Item 1: Nylon Fabric Blue (Shortage: 10 planned, 2 in stock)
        $itemShortage = MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'material_id' => $this->materialInShortage->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 10.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 100000.00,
            'is_subcon' => false,
        ]);

        // Mock current stock for Nylon Fabric Blue: 2.00 yards
        InventoryStock::create([
            'company_id' => $this->company->id,
            'material_id' => $this->materialInShortage->id,
            'quantity' => 2.00,
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Item 2: Subcon service
        $itemSubcon = MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'subcon_id' => $this->subcon->id,
            'planned_qty' => 1.00,
            'unit' => 'pcs',
            'unit_price' => 5000.00,
            'total_price' => 50000.00,
            'is_subcon' => true,
            'notes' => 'Sewing pocket panels',
        ]);

        // Item 3: Cotton Fabric Red with NO supplier assigned (should be skipped)
        $itemSkipped = MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'material_id' => $this->materialSkipped->id,
            'supplier_id' => null, // missing supplier
            'planned_qty' => 5.00,
            'unit' => 'yard',
            'unit_price' => 12000.00,
            'total_price' => 60000.00,
            'is_subcon' => false,
        ]);

        // 2. Load relationships and prepare stocks map
        $planning->load(['items.material', 'items.supplier', 'items.subcon']);
        $materialIds = $planning->items->pluck('material_id')->filter()->unique();
        $stocks = InventoryStock::whereIn('material_id', $materialIds)
            ->where('company_id', $this->company->id)
            ->select('material_id', \Illuminate\Support\Facades\DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('material_id')
            ->pluck('total_qty', 'material_id')
            ->toArray();

        // 3. Render the confirmation modal view
        $view = $this->view('filament.components.generate-po-modal', [
            'record' => $planning,
            'stocks' => $stocks,
        ]);

        // 4. Assertions on the rendered content
        // Verify Supplier section
        $view->assertSee('Supplier Purchase Orders');
        $view->assertSee('Supplier Textile Corp');
        $view->assertSee('Nylon Fabric Blue');
        $view->assertSee('MAT-BLUE-01');
        $view->assertSee('8.00'); // Order Qty (Shortage)
        
        // Verify Stock display and shortage calculation
        $view->assertSee('2.00 yard'); // Current stock
        $view->assertSee('Shortage: 8.00'); // Shortage warning

        // Verify total pricing and taxes
        // Subtotal = 8 * 10,000 = 80,000, PPN 11% = 8,800, Total = 88,800
        $view->assertSee('Rp 80.000,00'); // Subtotal format
        $view->assertSee('Rp 8.800,00'); // PPN format
        $view->assertSee('Rp 88.800,00'); // Grand total format

        // Verify Subcon section
        $view->assertSee('Subcontractor Services');
        $view->assertSee('Sewing Subcon Jogja');
        $view->assertSee('Sewing pocket panels');
        $view->assertSee('Rp 50.000,00');

        // Verify Warnings / Skipped items
        $view->assertSee('Skipped Items (No PO Will Be Generated)');
        $view->assertSee('Cotton Fabric Red');
        $view->assertSee('MAT-RED-02');
    }

    /**
     * Test that the generate PO action logic actually creates POs using the shortage quantity.
     */
    public function test_generate_po_action_creates_pos_with_shortage_qty(): void
    {
        $planning = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'design_id' => $this->design->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 100000,
            'total_subcon_cost' => 50000,
        ]);

        // Item 1: Nylon Fabric Blue (Shortage: 10 planned, 2 in stock)
        $itemShortage = MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'material_id' => $this->materialInShortage->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 10.00,
            'unit' => 'yard',
            'unit_price' => 10000.00,
            'total_price' => 100000.00,
            'is_subcon' => false,
        ]);

        // Mock current stock for Nylon Fabric Blue: 2.00 yards
        InventoryStock::create([
            'company_id' => $this->company->id,
            'material_id' => $this->materialInShortage->id,
            'quantity' => 2.00,
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Item 2: Fully Stocked Item (Planned: 5.00, Stock: 5.00) -> Should NOT generate PO item
        $fullyStockedMaterial = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-OK-01',
            'name' => 'Cotton Fabric Green',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 5000.00,
            'stock' => 0,
        ]);

        $itemFullyStocked = MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'material_id' => $fullyStockedMaterial->id,
            'supplier_id' => $this->supplier->id,
            'planned_qty' => 5.00,
            'unit' => 'yard',
            'unit_price' => 5000.00,
            'total_price' => 25000.00,
            'is_subcon' => false,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'material_id' => $fullyStockedMaterial->id,
            'quantity' => 5.00,
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Item 3: Subcon service
        $itemSubcon = MerchandisePlanningItem::create([
            'merchandise_planning_id' => $planning->id,
            'subcon_id' => $this->subcon->id,
            'planned_qty' => 1.00,
            'unit' => 'pcs',
            'unit_price' => 50000.00,
            'total_price' => 50000.00,
            'is_subcon' => true,
            'notes' => 'Sewing pocket panels',
        ]);

        // Execute the PO generation action logic via Livewire/Filament table testing helpers
        \Livewire\Livewire::test(\App\Filament\Resources\MerchandisePlanningResource\Pages\ListMerchandisePlannings::class)
            ->callTableAction('generatePO', $planning);

        // Assert Supplier PO was created only for the shortage quantity (8.00 nylon fabric)
        // Fully stocked Green Fabric (5.00 stock, 5.00 planned) has 0 shortage, so it shouldn't have a PO item
        $poSupplier = PoSupplier::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($poSupplier);
        $this->assertEquals(80000.00, $poSupplier->subtotal);
        $this->assertEquals(8800.00, $poSupplier->ppn_amount);
        $this->assertEquals(88800.00, $poSupplier->grand_total);

        // Verify PoSupplierItem
        $poSupplierItems = $poSupplier->items;
        $this->assertCount(1, $poSupplierItems); // Only Nylon Fabric Blue (shortage)
        $this->assertEquals($this->materialInShortage->id, $poSupplierItems->first()->material_id);
        $this->assertEquals(8.00, $poSupplierItems->first()->qty); // Shortage quantity!

        // Assert Subcon PO was created for the full qty (1.00)
        $poSubcon = PoSubcon::where('subcon_id', $this->subcon->id)->first();
        $this->assertNotNull($poSubcon);
        $this->assertEquals(50000.00, $poSubcon->service_cost);
        $this->assertCount(1, $poSubcon->items);
        $this->assertEquals(1.00, $poSubcon->items->first()->qty);
    }
}
