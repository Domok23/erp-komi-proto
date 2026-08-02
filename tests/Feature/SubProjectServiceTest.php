<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Company;
use App\Models\Project;
use App\Models\SubProject;
use App\Models\RdDesign;
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
            'status' => 'sampling',
            'bom_id' => $bom?->id,
            'target_qty' => 0,
            'produced_qty' => 0,
        ]);
    }

    public function test_has_sub_projects_and_pending_helpers(): void
    {
        $project = $this->seedProject();
        $this->assertFalse($project->hasSubProjects());
        $this->assertFalse($project->hasPendingSubProjectReviews());

        SubProject::create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'name' => 'Varian Hitam',
            'code' => 'BLK',
            'review_status' => 'pending',
            'target_qty' => 10,
            'produced_qty' => 0,
        ]);

        $project->refresh();
        $this->assertTrue($project->hasSubProjects());
        $this->assertTrue($project->hasPendingSubProjectReviews());
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
            'review_status' => 'pending',
            'target_qty' => 0,
            'produced_qty' => 0,
        ]);
        $this->assertTrue($inherited->effectiveBom()->is($baseBom));

        $overridden = SubProject::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'name' => 'Varian Hitam',
            'bom_id' => $overrideBom->id,
            'review_status' => 'pending',
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
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->subProjects());
        $this->assertSame(SubProject::class, get_class($project->subProjects()->getRelated()));
    }
}
