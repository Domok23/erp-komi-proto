<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Company;
use App\Models\Project;
use App\Models\SubProject;
use App\Models\RdDesign;
use App\Models\User;
use App\Services\SubProjectService;
use Carbon\Carbon;
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

    public function test_set_review_status_sets_audit_fields(): void
    {
        $project = $this->seedProject();
        $user = User::factory()->create();
        $sp = SubProject::create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'name' => 'Varian Hitam',
            'review_status' => 'pending',
            'target_qty' => 5,
            'produced_qty' => 0,
        ]);

        $updated = SubProjectService::setReviewStatus($sp, 'approved', $user->id, 'OK client');

        $this->assertSame('approved', $updated->review_status);
        $this->assertSame($user->id, $updated->reviewed_by);
        $this->assertSame('OK client', $updated->review_notes);
        $this->assertNotNull($updated->reviewed_at);
    }

    public function test_copy_for_transition_proto_resets_review(): void
    {
        $from = $this->seedProject();
        $sp = SubProject::create([
            'company_id' => $from->company_id,
            'project_id' => $from->id,
            'name' => 'Varian Hitam',
            'code' => 'BLK',
            'category' => 'colorway',
            'review_status' => 'approved',
            'reviewed_at' => Carbon::now(),
            'reviewed_by' => User::factory()->create()->id,
            'target_qty' => 5,
            'produced_qty' => 1,
        ]);

        $to = Project::create([
            'company_id' => $from->company_id,
            'project_code' => 'PRJ-SP-S',
            'name' => 'Tas Nike - Sample',
            'type' => 'sample',
            'status' => 'planning',
            'reference_project_id' => $from->id,
            'target_qty' => 1,
            'produced_qty' => 0,
        ]);

        SubProjectService::copyForTransition($from, $to, onlyApproved: false);

        $copy = $to->subProjects()->first();
        $this->assertNotNull($copy);
        $this->assertSame('Varian Hitam', $copy->name);
        $this->assertSame('BLK', $copy->code);
        $this->assertSame('colorway', $copy->category);
        $this->assertSame('pending', $copy->review_status);
        $this->assertNull($copy->reviewed_at);
        $this->assertNull($copy->reviewed_by);
        $this->assertSame(5, $copy->target_qty);
        $this->assertSame(0, $copy->produced_qty);
    }

    public function test_copy_for_transition_only_approved(): void
    {
        $from = $this->seedProject();
        SubProject::create([
            'company_id' => $from->company_id,
            'project_id' => $from->id,
            'name' => 'Varian Hitam',
            'review_status' => 'approved',
            'target_qty' => 5,
            'produced_qty' => 0,
        ]);
        SubProject::create([
            'company_id' => $from->company_id,
            'project_id' => $from->id,
            'name' => 'Varian Biru',
            'review_status' => 'rejected',
            'target_qty' => 3,
            'produced_qty' => 0,
        ]);

        $to = Project::create([
            'company_id' => $from->company_id,
            'project_code' => 'PRJ-SP-M',
            'name' => 'Tas Nike - Mass',
            'type' => 'mass',
            'status' => 'planning',
            'reference_project_id' => $from->id,
            'target_qty' => 100,
            'produced_qty' => 0,
        ]);

        SubProjectService::copyForTransition($from, $to, onlyApproved: true);

        $this->assertSame(1, $to->subProjects()->count());
        $this->assertSame('Varian Hitam', $to->subProjects()->first()->name);
    }
}
