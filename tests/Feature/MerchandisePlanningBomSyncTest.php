<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Company;
use App\Models\ConsumptionRate;
use App\Models\Material;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\Project;
use App\Models\RdDesign;
use App\Services\CodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchandisePlanningBomSyncTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private RdDesign $design;

    private Material $material;

    private Bom $bom;

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

        $this->design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'DES-SYNC-01',
            'name' => 'Sync Test Design',
            'product_type' => 'jacket',
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

        $this->bom = Bom::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'bom_number' => 'BOM-SYNC-001',
            'name' => 'Backpack BOM',
            'version' => '1.0',
            'status' => 'active',
        ]);

        $this->project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => 'Sync Test Project',
            'type' => 'mass',
            'status' => 'planning',
            'design_id' => $this->design->id,
            'bom_id' => $this->bom->id,
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
            'wastage_rate' => 10,
        ]);

        // 2. Assert BOM item was created
        $bomItem = BomItem::where('bom_id', $this->bom->id)
            ->where('material_id', $this->material->id)
            ->first();
        $this->assertNotNull($bomItem);

        // 3. Assert MerchandisePlanningItem was automatically created
        $planItem = MerchandisePlanningItem::where('merchandise_planning_id', $this->planning->id)
            ->where('material_id', $this->material->id)
            ->first();
        $this->assertNotNull($planItem);
        $this->assertEquals('Main Body', $planItem->component);

        // planned_qty = 2.0 (rate) * 10 (target_qty) * (1 + 3/100 config wastage) = 20.6
        $expectedWastageMultiplier = 1 + (((float) ($bomItem->wastage_percent ?? 0)) / 100);
        $expectedPlannedQty = 2.0 * 10 * $expectedWastageMultiplier;
        $this->assertEquals($expectedPlannedQty, (float) $planItem->planned_qty);

        // 4. Assert totals recalculated
        $this->planning->refresh();
        $this->assertEquals($planItem->total_price, $this->planning->total_material_cost);
    }

    public function test_bom_item_direct_addition_and_update_auto_syncs_to_merchandise_planning(): void
    {
        // 1. Direct BOM Item creation
        $bomItem = BomItem::create([
            'bom_id' => $this->bom->id,
            'material_id' => $this->material->id,
            'component' => 'Zipper Front',
            'quantity_per_unit' => 1.5,
            'unit' => 'meter',
            'wastage_percent' => 5,
            'is_from_rnd' => true,
        ]);

        $planItem = MerchandisePlanningItem::where('merchandise_planning_id', $this->planning->id)
            ->where('material_id', $this->material->id)
            ->first();
        $this->assertNotNull($planItem);
        // 1.5 * 10 * 1.05 = 15.75
        $this->assertEquals(15.75, (float) $planItem->planned_qty);

        // 2. Direct BOM Item update
        $bomItem->update([
            'quantity_per_unit' => 3.0,
        ]);

        $planItem->refresh();
        // 3.0 * 10 * 1.05 = 31.50
        $this->assertEquals(31.50, (float) $planItem->planned_qty);
    }

    public function test_bom_item_deletion_removes_rnd_item_from_merchandise_planning(): void
    {
        $bomItem = BomItem::create([
            'bom_id' => $this->bom->id,
            'material_id' => $this->material->id,
            'component' => 'Shoulder Strap',
            'quantity_per_unit' => 1.0,
            'unit' => 'meter',
            'wastage_percent' => 0,
            'is_from_rnd' => true,
        ]);

        $this->assertDatabaseHas('merchandise_planning_items', [
            'merchandise_planning_id' => $this->planning->id,
            'material_id' => $this->material->id,
        ]);

        // Delete BOM item
        $bomItem->delete();

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

        // Create a new BOM item
        BomItem::create([
            'bom_id' => $this->bom->id,
            'material_id' => $this->material->id,
            'quantity_per_unit' => 1.0,
            'unit' => 'meter',
            'is_from_rnd' => true,
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

        // Create BOM item
        $bomItem = BomItem::create([
            'bom_id' => $this->bom->id,
            'material_id' => $this->material->id,
            'quantity_per_unit' => 2.0,
            'unit' => 'meter',
            'is_from_rnd' => true,
        ]);

        $this->planning->refresh();
        $this->assertCount(2, $this->planning->items);
        $this->assertEquals(50000, $this->planning->total_subcon_cost);

        // Delete BOM item
        $bomItem->delete();

        // Manual item must still exist
        $this->planning->refresh();
        $this->assertCount(1, $this->planning->items);
        $this->assertEquals($manualItem->id, $this->planning->items->first()->id);
        $this->assertEquals(50000, $this->planning->total_subcon_cost);
    }

    public function test_project_target_qty_change_recalculates_planned_qty_in_merchandise_planning(): void
    {
        BomItem::create([
            'bom_id' => $this->bom->id,
            'material_id' => $this->material->id,
            'quantity_per_unit' => 2.0,
            'unit' => 'meter',
            'wastage_percent' => 0,
            'is_from_rnd' => true,
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

    public function test_discontinued_bom_does_not_sync_from_rnd_or_to_plannings(): void
    {
        $this->bom->update(['status' => 'discontinued']);

        // 1. Consumption rate change should not create BOM item in discontinued BOM
        ConsumptionRate::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'material_id' => $this->material->id,
            'component' => 'Discontinued Part',
            'standard_rate' => 1.0,
            'unit' => 'meter',
        ]);

        $this->assertDatabaseMissing('bom_items', [
            'bom_id' => $this->bom->id,
            'material_id' => $this->material->id,
        ]);

        // 2. Planning items should remain empty
        $this->assertCount(0, $this->planning->items);
    }

    public function test_completed_or_archived_project_is_protected_from_sync(): void
    {
        $this->project->update(['status' => 'completed']);

        BomItem::create([
            'bom_id' => $this->bom->id,
            'material_id' => $this->material->id,
            'quantity_per_unit' => 2.0,
            'unit' => 'meter',
            'is_from_rnd' => true,
        ]);

        $this->planning->refresh();
        $this->assertCount(0, $this->planning->items);
    }
}
