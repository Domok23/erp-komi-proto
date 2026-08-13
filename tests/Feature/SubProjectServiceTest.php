<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Company;
use App\Models\Project;
use App\Models\RdDesign;
use App\Models\SubProject;
use App\Models\User;
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

        $overridden = SubProject::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'name' => 'Varian Hitam',
            'bom_id' => $overrideBom->id,
            'target_qty' => 0,
            'produced_qty' => 0,
        ]);
        $this->assertTrue($overridden->effectiveBom()->is($overrideBom));
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

        SubProject::create([
            'company_id' => $proto->company_id,
            'project_id' => $proto->id,
            'name' => 'Varian Hitam',
            'target_qty' => 1,
            'produced_qty' => 0,
        ]);

        $sample = ProjectTransitionService::approveProject($proto, $user->id);

        $this->assertSame('sample', $sample->type);
        $this->assertSame(1, $sample->subProjects()->count());
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
}
