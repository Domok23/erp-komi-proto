<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\HrEmployee;
use App\Models\Project;
use App\Models\User;
use App\Services\PlaceEmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectEmployeePlacementRelationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private Project $project;

    private HrEmployee $employee1;

    private HrEmployee $employee2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Komi Corp',
            'code' => 'KOMI',
            'address' => 'Jakarta',
        ]);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'code' => 'CUS-01',
            'name' => 'Komi Client',
            'is_active' => true,
        ]);

        $this->project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-2026-001',
            'name' => 'Production Project A',
            'type' => 'mass',
            'status' => 'production',
            'customer_id' => $customer->id,
        ]);

        $this->employee1 = HrEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-001',
            'name' => 'Alice Production',
            'status' => 'active',
            'join_date' => '2026-01-01',
            'created_by' => $this->user->id,
        ]);

        $this->employee2 = HrEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-002',
            'name' => 'Bob Operator',
            'status' => 'active',
            'join_date' => '2026-02-01',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_project_has_many_placements(): void
    {
        PlaceEmployeeService::place($this->employee1, $this->project, '2026-07-01', $this->user->id);
        PlaceEmployeeService::place($this->employee2, $this->project, '2026-07-05', $this->user->id);

        $this->assertCount(2, $this->project->placements);
        $this->assertTrue($this->project->placements->contains('employee_id', $this->employee1->id));
        $this->assertTrue($this->project->placements->contains('employee_id', $this->employee2->id));
    }

    public function test_project_active_placements_only_returns_active_status(): void
    {
        $placement1 = PlaceEmployeeService::place($this->employee1, $this->project, '2026-07-01', $this->user->id);

        $project2 = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-2026-002',
            'name' => 'Project B',
            'type' => 'mass',
            'status' => 'planning',
            'customer_id' => $this->project->customer_id,
        ]);

        // Move employee1 to project2, ending placement1 on project1
        PlaceEmployeeService::place($this->employee1, $project2, '2026-07-15', $this->user->id);

        // Place employee2 on project1
        PlaceEmployeeService::place($this->employee2, $this->project, '2026-07-10', $this->user->id);

        // Project 1 should have 2 total placements (1 ended, 1 active)
        $this->assertCount(2, $this->project->fresh()->placements);

        // Project 1 activePlacements should only have 1 (employee2)
        $activePlacements = $this->project->fresh()->activePlacements;
        $this->assertCount(1, $activePlacements);
        $this->assertSame($this->employee2->id, $activePlacements->first()->employee_id);
    }
}
