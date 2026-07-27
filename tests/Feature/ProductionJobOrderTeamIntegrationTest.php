<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\HrEmployee;
use App\Models\JobOrder;
use App\Models\MerchandisePlanning;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\User;
use App\Services\PlaceEmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionJobOrderTeamIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private Project $project;

    private MerchandisePlanning $planning;

    private ProductionOrder $productionOrder;

    private HrEmployee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Komi Production Plant',
            'code' => 'KPP',
            'address' => 'Surabaya',
        ]);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'code' => 'CUS-PROD',
            'name' => 'Manufacturing Client',
            'is_active' => true,
        ]);

        $this->project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-PROD-001',
            'name' => 'Mass Production Garment Batch 1',
            'type' => 'mass',
            'status' => 'production',
            'customer_id' => $customer->id,
        ]);

        $this->planning = MerchandisePlanning::create([
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'planning_date' => '2026-07-01',
            'status' => 'finalised',
        ]);

        $this->productionOrder = ProductionOrder::create([
            'company_id' => $this->company->id,
            'production_number' => 'PO-2026-001',
            'project_id' => $this->project->id,
            'merchandising_planning_id' => $this->planning->id,
            'planned_qty' => 1000,
            'completed_qty' => 0,
            'status' => 'in_progress',
        ]);

        $this->employee = HrEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'OPR-101',
            'name' => 'Charlie Operator',
            'status' => 'active',
            'join_date' => '2026-03-01',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_production_order_accesses_project_placements(): void
    {
        PlaceEmployeeService::place($this->employee, $this->project, '2026-07-01', $this->user->id);

        $placements = $this->productionOrder->projectPlacements;

        $this->assertCount(1, $placements);
        $this->assertSame($this->employee->id, $placements->first()->employee_id);
    }

    public function test_job_order_can_be_assigned_to_multiple_project_employees(): void
    {
        $employee2 = HrEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'OPR-102',
            'name' => 'Dave Operator',
            'status' => 'active',
            'join_date' => '2026-03-01',
            'created_by' => $this->user->id,
        ]);

        PlaceEmployeeService::place($this->employee, $this->project, '2026-07-01', $this->user->id);
        PlaceEmployeeService::place($employee2, $this->project, '2026-07-01', $this->user->id);

        $jobOrder = JobOrder::create([
            'company_id' => $this->company->id,
            'production_order_id' => $this->productionOrder->id,
            'merchandising_planning_id' => $this->planning->id,
            'job_order_number' => 'JO-2026-001',
            'task_type' => 'sewing',
            'planned_qty' => 500,
            'completed_qty' => 0,
            'status' => 'pending',
            'assigned_to' => [$this->employee->name, $employee2->name],
        ]);

        $this->assertIsArray($jobOrder->fresh()->assigned_to);
        $this->assertCount(2, $jobOrder->fresh()->assigned_to);
        $this->assertContains('Charlie Operator', $jobOrder->fresh()->assigned_to);
        $this->assertContains('Dave Operator', $jobOrder->fresh()->assigned_to);
    }
}
