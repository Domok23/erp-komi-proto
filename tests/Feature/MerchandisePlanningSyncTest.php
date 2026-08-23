<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ConsumptionRate;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialUom;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\Project;
use App\Models\RdDesign;
use App\Models\Supplier;
use App\Services\CodeGenerator;
use App\Services\MerchandisePlanningSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchandisePlanningSyncTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private RdDesign $design;

    private Material $material;

    private Project $project;

    private MerchandisePlanning $planning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Sync Test Company',
            'code' => 'STC',
            'address' => 'Test Address',
        ]);
        session(['active_company_id' => $this->company->id]);

        $this->design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'DES-SYNC-01',
            'name' => 'Sync Test Design',
            'product_type' => 'backpack',
            'version' => '1.0',
            'status' => 'approved',
        ]);

        $this->material = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-SYNC-01',
            'name' => 'Cordura Fabric',
            'category' => 'fabric',
            'unit' => 'meter',
            'price' => 20000,
            'stock' => 50,
        ]);

        $this->project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => 'Sync Test Project',
            'type' => 'mass',
            'status' => 'planning',
            'design_id' => $this->design->id,
            'target_qty' => 10,
        ]);

        $this->planning = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'design_id' => $this->design->id,
            'planning_date' => now()->toDateString(),
            'status' => 'preliminary',
            'total_material_cost' => 0,
            'total_subcon_cost' => 0,
        ]);
    }

    public function test_sync_from_design_populates_items_correctly(): void
    {
        $uom = MaterialUom::firstOrCreate(['company_id' => $this->company->id, 'code' => 'MTR'], ['name' => 'Meter']);
        $cat = MaterialCategory::firstOrCreate(['company_id' => $this->company->id, 'code' => 'FAB'], ['name' => 'Fabric Category']);
        $supplier = Supplier::firstOrCreate(['company_id' => $this->company->id, 'code' => 'SUPA'], ['name' => 'Supplier A']);

        $mat = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT01',
            'name' => 'Nylon Fabric',
            'category' => 'Fabric Category',
            'category_id' => $cat->id,
            'uom_id' => $uom->id,
            'supplier_id' => $supplier->id,
            'price' => 20000,
        ]);

        $design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'DSG-001',
            'name' => 'Backpack Alpha',
            'version' => '1.0',
            'status' => 'approved',
            'product_type' => 'backpack',
        ]);

        ConsumptionRate::create([
            'company_id' => $this->company->id,
            'design_id' => $design->id,
            'material_id' => $mat->id,
            'component' => 'Front Pocket',
            'standard_rate' => 0.25,
            'unit' => 'MTR',
            'wastage_rate' => 4,
        ]);

        $project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-001',
            'name' => 'Backpack Order',
            'design_id' => $design->id,
            'target_qty' => 100,
            'status' => 'in_progress',
        ]);

        $planning = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'design_id' => $design->id,
            'status' => 'draft',
        ]);

        $syncedCount = MerchandisePlanningSyncService::syncFromDesign($planning);

        $this->assertEquals(1, $syncedCount);
        $this->assertDatabaseHas('merchandise_planning_items', [
            'merchandise_planning_id' => $planning->id,
            'material_id' => $mat->id,
            'component' => 'Front Pocket',
            'planned_qty' => 26.0, // 0.25 * 100 * 1.04
            'is_from_rnd' => true,
        ]);
    }

    public function test_consumption_rate_addition_auto_syncs_to_merchandise_planning(): void
    {
        // 1. Create a consumption rate in R&D
        $rate = ConsumptionRate::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'material_id' => $this->material->id,
            'component' => 'Main Body',
            'standard_rate' => 2.0,
            'unit' => 'meter',
            'wastage_rate' => 3,
        ]);

        // 2. Assert MerchandisePlanningItem was automatically created directly from R&D
        $planItem = MerchandisePlanningItem::where('merchandise_planning_id', $this->planning->id)
            ->where('material_id', $this->material->id)
            ->first();
        $this->assertNotNull($planItem);
        $this->assertEquals('Main Body', $planItem->component);

        // planned_qty = 2.0 (rate) * 10 (target_qty) * (1 + 3/100 config wastage) = 20.6
        $expectedPlannedQty = 2.0 * 10 * 1.03;
        $this->assertEquals($expectedPlannedQty, (float) $planItem->planned_qty);

        // 3. Assert totals recalculated
        $this->planning->refresh();
        $this->assertEquals($planItem->total_price, $this->planning->total_material_cost);
    }

    public function test_consumption_rate_update_auto_syncs_to_merchandise_planning(): void
    {
        $rate = ConsumptionRate::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'material_id' => $this->material->id,
            'component' => 'Zipper Front',
            'standard_rate' => 1.5,
            'unit' => 'meter',
            'wastage_rate' => 5,
        ]);

        $planItem = MerchandisePlanningItem::where('merchandise_planning_id', $this->planning->id)
            ->where('material_id', $this->material->id)
            ->first();
        $this->assertNotNull($planItem);
        $this->assertEquals(15.75, (float) $planItem->planned_qty);

        // Update rate
        $rate->update([
            'standard_rate' => 3.0,
        ]);

        $planItem->refresh();
        // 3.0 * 10 * 1.05 = 31.50
        $this->assertEquals(31.50, (float) $planItem->planned_qty);
    }

    public function test_consumption_rate_deletion_removes_rnd_item_from_merchandise_planning(): void
    {
        $rate = ConsumptionRate::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'material_id' => $this->material->id,
            'component' => 'Shoulder Strap',
            'standard_rate' => 1.0,
            'unit' => 'meter',
            'wastage_rate' => 0,
        ]);

        $this->assertDatabaseHas('merchandise_planning_items', [
            'merchandise_planning_id' => $this->planning->id,
            'material_id' => $this->material->id,
        ]);

        // Delete consumption rate
        $rate->delete();

        $this->assertDatabaseMissing('merchandise_planning_items', [
            'merchandise_planning_id' => $this->planning->id,
            'material_id' => $this->material->id,
        ]);

        $this->planning->refresh();
        $this->assertEquals(0, $this->planning->total_material_cost);
    }

    public function test_finalised_merchandise_planning_is_protected_from_auto_sync(): void
    {
        $this->planning->update(['status' => 'finalised']);

        // Create a new consumption rate
        ConsumptionRate::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'material_id' => $this->material->id,
            'standard_rate' => 1.0,
            'unit' => 'meter',
        ]);

        // Planning should remain untouched
        $this->assertCount(0, $this->planning->items);
    }

    public function test_manual_merchandise_planning_items_are_preserved_during_sync(): void
    {
        // Create manual subcon service item in planning
        $manualItem = MerchandisePlanningItem::create([
            'merchandise_planning_id' => $this->planning->id,
            'material_id' => null,
            'component' => 'Embroidery Logo Service',
            'planned_qty' => 10,
            'unit' => 'pcs',
            'unit_price' => 5000,
            'total_price' => 50000,
            'is_subcon' => true,
            'is_from_rnd' => false,
        ]);

        // Create consumption rate
        $rate = ConsumptionRate::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'material_id' => $this->material->id,
            'standard_rate' => 2.0,
            'unit' => 'meter',
        ]);

        $this->planning->refresh();
        $this->assertCount(2, $this->planning->items);
        $this->assertEquals(50000, $this->planning->total_subcon_cost);

        // Delete consumption rate
        $rate->delete();

        // Manual item must still exist
        $this->planning->refresh();
        $this->assertCount(1, $this->planning->items);
        $this->assertEquals($manualItem->id, $this->planning->items->first()->id);
        $this->assertEquals(50000, $this->planning->total_subcon_cost);
    }

    public function test_project_target_qty_change_recalculates_planned_qty_in_merchandise_planning(): void
    {
        ConsumptionRate::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'material_id' => $this->material->id,
            'standard_rate' => 2.0,
            'unit' => 'meter',
            'wastage_rate' => 0,
        ]);

        $planItem = MerchandisePlanningItem::where('merchandise_planning_id', $this->planning->id)
            ->where('material_id', $this->material->id)
            ->first();
        // 2.0 * 10 target_qty = 20.0
        $this->assertEquals(20.0, (float) $planItem->planned_qty);

        // Update project target_qty to 50
        $this->project->update(['target_qty' => 50]);

        $planItem->refresh();
        // 2.0 * 50 target_qty = 100.0
        $this->assertEquals(100.0, (float) $planItem->planned_qty);
    }

    public function test_completed_or_archived_project_is_protected_from_sync(): void
    {
        $this->project->update(['status' => 'completed']);

        ConsumptionRate::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'material_id' => $this->material->id,
            'standard_rate' => 2.0,
            'unit' => 'meter',
        ]);

        $this->planning->refresh();
        $this->assertCount(0, $this->planning->items);
    }
}
