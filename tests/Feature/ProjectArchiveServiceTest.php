<?php

namespace Tests\Feature;

use App\Exceptions\ProjectArchiveException;
use App\Models\Company;
use App\Models\Costing;
use App\Models\MerchandisePlanning;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectArchiveServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedCompanyAndUser(string $role = 'staff'): array
    {
        $company = Company::create([
            'name' => 'PT Archive Test '.uniqid(),
            'code' => 'ARC-'.uniqid(),
            'address' => 'Jogja',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => $role,
        ]);

        return [$company, $user];
    }

    private function makeProject(Company $company, array $overrides = []): Project
    {
        return Project::create(array_merge([
            'company_id' => $company->id,
            'project_code' => 'PRJ-'.uniqid(),
            'name' => 'Test Project',
            'type' => 'proto',
            'status' => 'completed',
            'target_qty' => 1,
            'produced_qty' => 0,
        ], $overrides));
    }

    public function test_active_scope_excludes_archived_projects(): void
    {
        [$company] = $this->seedCompanyAndUser();

        $active = $this->makeProject($company, ['project_code' => 'PRJ-ACTIVE']);
        $archived = $this->makeProject($company, [
            'project_code' => 'PRJ-ARCH',
            'archived_at' => now(),
        ]);

        $ids = Project::active()->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($archived->id, $ids);
        $this->assertTrue($archived->fresh()->isArchived());
        $this->assertFalse($active->fresh()->isArchived());
    }

    public function test_user_is_admin_helper(): void
    {
        [, $admin] = $this->seedCompanyAndUser('admin');
        [, $staff] = $this->seedCompanyAndUser('staff');

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($staff->isAdmin());
    }

    public function test_archive_completed_without_blockers_succeeds(): void
    {
        [$company, $user] = $this->seedCompanyAndUser('staff');
        $project = $this->makeProject($company, ['status' => 'completed']);

        ProjectArchiveService::archive($project, $user);

        $project->refresh();
        $this->assertNotNull($project->archived_at);
        $this->assertSame($user->id, $project->archived_by);
    }

    public function test_archive_planning_as_non_admin_fails(): void
    {
        [$company, $user] = $this->seedCompanyAndUser('staff');
        $project = $this->makeProject($company, ['status' => 'planning']);

        $this->expectException(ProjectArchiveException::class);
        ProjectArchiveService::archive($project, $user);
    }

    public function test_archive_completed_with_open_po_as_non_admin_fails_with_blocker(): void
    {
        [$company, $user] = $this->seedCompanyAndUser('staff');
        $project = $this->makeProject($company, ['status' => 'completed']);

        $plan = MerchandisePlanning::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 0,
            'total_subcon_cost' => 0,
        ]);

        ProductionOrder::create([
            'company_id' => $company->id,
            'production_number' => 'PO-OPEN-1',
            'project_id' => $project->id,
            'merchandising_planning_id' => $plan->id,
            'planned_qty' => 10,
            'completed_qty' => 0,
            'status' => 'in_progress',
        ]);

        try {
            ProjectArchiveService::archive($project, $user);
            $this->fail('Expected ProjectArchiveException');
        } catch (ProjectArchiveException $e) {
            $this->assertNotEmpty($e->blockers());
            $this->assertSame('production_order', $e->blockers()[0]['type']);
        }
    }

    public function test_admin_force_archive_ongoing_with_blockers_succeeds(): void
    {
        [$company, $admin] = $this->seedCompanyAndUser('admin');
        $project = $this->makeProject($company, ['status' => 'production']);

        $plan = MerchandisePlanning::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 0,
            'total_subcon_cost' => 0,
        ]);

        ProductionOrder::create([
            'company_id' => $company->id,
            'production_number' => 'PO-OPEN-2',
            'project_id' => $project->id,
            'merchandising_planning_id' => $plan->id,
            'planned_qty' => 10,
            'completed_qty' => 0,
            'status' => 'planned',
        ]);

        ProjectArchiveService::archive($project, $admin, force: true);

        $this->assertTrue($project->fresh()->isArchived());
    }

    public function test_non_admin_restore_fails_admin_restore_succeeds(): void
    {
        [$company, $staff] = $this->seedCompanyAndUser('staff');
        [, $admin] = $this->seedCompanyAndUser('admin');
        $project = $this->makeProject($company, [
            'status' => 'completed',
            'archived_at' => now(),
            'archived_by' => $admin->id,
        ]);

        try {
            ProjectArchiveService::restore($project, $staff);
            $this->fail('Expected ProjectArchiveException');
        } catch (ProjectArchiveException $e) {
            $this->assertTrue($project->fresh()->isArchived());
        }

        ProjectArchiveService::restore($project, $admin);
        $project->refresh();
        $this->assertNull($project->archived_at);
        $this->assertNull($project->archived_by);
    }

    public function test_archive_already_archived_is_noop(): void
    {
        [$company, $user] = $this->seedCompanyAndUser('staff');
        $archivedAt = now()->subDay()->startOfSecond();
        $project = $this->makeProject($company, [
            'status' => 'completed',
            'archived_at' => $archivedAt,
            'archived_by' => $user->id,
        ]);

        ProjectArchiveService::archive($project, $user);

        $project->refresh();
        $this->assertSame($archivedAt->toDateTimeString(), $project->archived_at->toDateTimeString());
    }

    public function test_blockers_include_open_merch_and_costing(): void
    {
        [$company] = $this->seedCompanyAndUser();
        $project = $this->makeProject($company);

        MerchandisePlanning::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'planning_date' => now()->toDateString(),
            'status' => 'preliminary',
            'total_material_cost' => 0,
            'total_subcon_cost' => 0,
        ]);

        Costing::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'costing_date' => now()->toDateString(),
            'version' => '1.0',
            'status' => 'draft',
            'currency' => 'IDR',
        ]);

        $blockers = ProjectArchiveService::blockers($project);
        $types = array_column($blockers, 'type');

        $this->assertContains('merchandise_planning', $types);
        $this->assertContains('costing', $types);
    }
}
