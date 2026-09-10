<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Company;
use App\Models\ConsumptionRate;
use App\Models\Material;
use App\Models\MerchandisePlanning;
use App\Models\Project;
use App\Models\RdDesign;
use App\Models\SubProject;
use App\Models\User;
use App\Services\CostingCalculatorService;
use App\Services\MerchandisePlanningSyncService;
use App\Services\ProjectTransitionService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedProject(?Bom $bom = null): Project
    {
        $company = Company::create([
            'name' => 'Test Co',
            'code' => 'TST',
            'address' => 'X',
        ]);

        return Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-SP-001',
            'name' => 'Tas Nike',
            'type' => 'sample',
            'status' => 'planning',
            'bom_id' => $bom?->id,
            'target_qty' => 0,
            'produced_qty' => 0,
        ]);
    }

    public function test_has_sub_projects(): void
    {
        $project = $this->seedProject();
        $this->assertFalse($project->hasSubProjects());

        SubProject::create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'name' => 'Varian Hitam',
            'code' => 'BLK',
            'target_qty' => 10,
            'produced_qty' => 0,
        ]);

        $project->refresh();
        $this->assertTrue($project->hasSubProjects());
    }

    public function test_effective_bom_inherits_then_overrides(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TST2', 'address' => 'X']);

        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DSN-1',
            'name' => 'Design Nike',
            'product_type' => 'other',
            'status' => 'approved',
        ]);
        $design2 = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DSN-2',
            'name' => 'Design Nike Black',
            'product_type' => 'other',
            'status' => 'approved',
        ]);
        $baseBom = Bom::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'name' => 'Base',
            'status' => 'active',
        ]);
        $overrideBom = Bom::create([
            'company_id' => $company->id,
            'design_id' => $design2->id,
            'name' => 'Black',
            'status' => 'active',
        ]);

        $project = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-SP-002',
            'name' => 'Tas Nike',
            'type' => 'sample',
            'status' => 'sampling',
            'bom_id' => $baseBom->id,
            'design_id' => $design->id,
            'target_qty' => 0,
            'produced_qty' => 0,
        ]);

        $inherited = SubProject::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'name' => 'Varian Putih',
            'target_qty' => 0,
            'produced_qty' => 0,
        ]);
        $this->assertTrue($inherited->effectiveBom()->is($baseBom));
        $this->assertTrue($inherited->effectiveDesign()->is($design));

        $overridden = SubProject::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'name' => 'Varian Hitam',
            'design_id' => $design2->id,
            'bom_id' => $overrideBom->id,
            'target_qty' => 0,
            'produced_qty' => 0,
        ]);
        $this->assertTrue($overridden->effectiveBom()->is($overrideBom));
        $this->assertTrue($overridden->effectiveDesign()->is($design2));
    }

    public function test_lifecycle_children_relation_name(): void
    {
        $project = $this->seedProject();
        $child = Project::create([
            'company_id' => $project->company_id,
            'project_code' => 'PRJ-SP-003',
            'name' => 'Tas Nike - Mass',
            'type' => 'mass',
            'status' => 'planning',
            'reference_project_id' => $project->id,
            'target_qty' => 100,
            'produced_qty' => 0,
        ]);

        $this->assertTrue($project->lifecycleChildren->contains('id', $child->id));
        $this->assertInstanceOf(HasMany::class, $project->subProjects());
        $this->assertSame(SubProject::class, get_class($project->subProjects()->getRelated()));
    }

    public function test_proto_to_sample_copies_all(): void
    {
        $user = User::factory()->create();
        $proto = $this->seedProject();
        $proto->update(['type' => 'proto', 'status' => 'development']);

        $protoDesign = RdDesign::create([
            'company_id' => $proto->company_id,
            'code' => 'D-PR-01',
            'name' => 'Proto Design',
            'product_type' => 'backpack',
            'status' => 'approved',
        ]);

        SubProject::create([
            'company_id' => $proto->company_id,
            'project_id' => $proto->id,
            'name' => 'Varian Hitam',
            'design_id' => $protoDesign->id,
            'target_qty' => 1,
            'produced_qty' => 0,
        ]);

        $sample = ProjectTransitionService::approveProject($proto, $user->id);

        $this->assertSame('sample', $sample->type);
        $this->assertSame(1, $sample->subProjects()->count());
        $this->assertSame($protoDesign->id, $sample->subProjects()->first()->design_id);
    }

    public function test_duplicate_copies_sub_projects(): void
    {
        $project = $this->seedProject();
        SubProject::create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'name' => 'Varian Hitam',
            'target_qty' => 2,
            'produced_qty' => 0,
        ]);

        $copy = ProjectTransitionService::duplicateProject($project);

        $this->assertSame(1, $copy->subProjects()->count());
    }

    public function test_sub_project_rnd_override_syncs_to_merchandise_planning_and_costing(): void
    {
        $company = Company::create(['name' => 'C', 'code' => 'C', 'address' => 'X']);
        $materialA = Material::create([
            'company_id' => $company->id,
            'code' => 'M-A',
            'name' => 'Material A',
            'category' => 'fabric',
            'price' => 10000,
        ]);
        $materialB = Material::create([
            'company_id' => $company->id,
            'code' => 'M-B',
            'name' => 'Material B',
            'category' => 'fabric',
            'price' => 25000,
        ]);

        $baseDesign = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'D-BASE',
            'name' => 'Base Design',
            'product_type' => 'backpack',
            'status' => 'approved',
        ]);
        ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $baseDesign->id,
            'material_id' => $materialA->id,
            'component' => 'Body',
            'standard_rate' => 1.0,
            'unit' => 'meter',
            'wastage_rate' => 0,
        ]);

        $overrideDesign = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'D-OVR',
            'name' => 'Override Design',
            'product_type' => 'backpack',
            'status' => 'approved',
        ]);
        ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $overrideDesign->id,
            'material_id' => $materialB->id,
            'component' => 'Body Leather',
            'standard_rate' => 2.0,
            'unit' => 'meter',
            'wastage_rate' => 0,
        ]);

        $project = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-TEST-OVR',
            'name' => 'Project with SubProject Override',
            'type' => 'sample',
            'status' => 'planning',
            'design_id' => $baseDesign->id,
            'target_qty' => 10,
            'produced_qty' => 0,
        ]);

        $subProject = SubProject::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'name' => 'Leather Variant',
            'design_id' => $overrideDesign->id,
            'target_qty' => 5,
            'produced_qty' => 0,
        ]);

        // 1. Costing calculation uses overrideDesign
        $costingCalc = CostingCalculatorService::calculateFromDesignOrBom($project, $subProject);
        $expectedMaterialCost = 2.0 * 25000;
        $this->assertEqualsWithDelta($expectedMaterialCost, $costingCalc['material_cost'], 0.01);

        // 2. Merchandise Planning sync uses overrideDesign & subProject target_qty
        $planning = MerchandisePlanning::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'sub_project_id' => $subProject->id,
            'planning_date' => now()->toDateString(),
            'status' => 'preliminary',
        ]);

        $count = MerchandisePlanningSyncService::syncFromDesign($planning);
        $this->assertSame(1, $count);

        $planningItem = $planning->items()->first();
        $this->assertSame($materialB->id, $planningItem->material_id);
        $this->assertSame('Body Leather', $planningItem->component);
        $this->assertEqualsWithDelta(5 * 2.0, (float) $planningItem->planned_qty, 0.01);
    }
}
