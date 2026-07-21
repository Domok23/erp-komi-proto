<?php

namespace Tests\Feature;

use App\Exceptions\HrPlacementException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\HrEmployee;
use App\Models\HrEmployeePlacement;
use App\Models\Project;
use App\Models\User;
use App\Services\PlaceEmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPlaceEmployeeTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Company $otherCompany;

    private User $user;

    private Project $project;

    private Project $otherProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Komi A',
            'code' => 'A',
            'address' => 'Jakarta',
        ]);
        $this->otherCompany = Company::create([
            'name' => 'Komi B',
            'code' => 'B',
            'address' => 'Bandung',
        ]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'code' => 'CUS-1',
            'name' => 'Customer',
            'is_active' => true,
        ]);

        $this->project = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-1',
            'name' => 'Project One',
            'type' => 'mass',
            'status' => 'planning',
            'customer_id' => $customer->id,
        ]);

        $otherCustomer = Customer::create([
            'company_id' => $this->otherCompany->id,
            'code' => 'CUS-2',
            'name' => 'Other Customer',
            'is_active' => true,
        ]);

        $this->otherProject = Project::create([
            'company_id' => $this->otherCompany->id,
            'project_code' => 'PRJ-2',
            'name' => 'Project Two',
            'type' => 'mass',
            'status' => 'planning',
            'customer_id' => $otherCustomer->id,
        ]);
    }

    private function makeEmployee(): HrEmployee
    {
        return HrEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-100',
            'name' => 'Budi',
            'status' => 'active',
            'join_date' => '2026-07-01',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_place_creates_active_placement(): void
    {
        $employee = $this->makeEmployee();

        $placement = PlaceEmployeeService::place(
            $employee,
            $this->project,
            '2026-07-16',
            $this->user->id
        );

        $this->assertSame('active', $placement->status);
        $this->assertSame($this->project->id, $placement->project_id);
        $this->assertNull($placement->end_date);
    }

    public function test_place_ends_previous_active_placement(): void
    {
        $employee = $this->makeEmployee();

        $first = PlaceEmployeeService::place($employee, $this->project, '2026-07-01', $this->user->id);

        $project2 = Project::create([
            'company_id' => $this->company->id,
            'project_code' => 'PRJ-1B',
            'name' => 'Project 1B',
            'type' => 'mass',
            'status' => 'planning',
            'customer_id' => $this->project->customer_id,
        ]);

        $second = PlaceEmployeeService::place($employee, $project2, '2026-07-16', $this->user->id);

        $this->assertSame('ended', $first->fresh()->status);
        $this->assertNotNull($first->fresh()->end_date);
        $this->assertSame('active', $second->status);
        $this->assertSame(1, HrEmployeePlacement::where('employee_id', $employee->id)->where('status', 'active')->count());
    }

    public function test_cannot_place_on_other_company_project(): void
    {
        $employee = $this->makeEmployee();

        $this->expectException(HrPlacementException::class);
        $this->expectExceptionMessage('company');

        PlaceEmployeeService::place($employee, $this->otherProject, '2026-07-16', $this->user->id);
    }

    public function test_cannot_place_inactive_employee(): void
    {
        $employee = $this->makeEmployee();
        $employee->update(['status' => 'inactive']);

        $this->expectException(HrPlacementException::class);
        $this->expectExceptionMessage('inactive');

        PlaceEmployeeService::place($employee, $this->project, '2026-07-16', $this->user->id);
    }

    public function test_deactivate_ends_active_placement(): void
    {
        $employee = $this->makeEmployee();
        PlaceEmployeeService::place($employee, $this->project, '2026-07-01', $this->user->id);

        PlaceEmployeeService::deactivate($employee, $this->user->id);

        $this->assertSame('inactive', $employee->fresh()->status);
        $this->assertSame(0, HrEmployeePlacement::where('employee_id', $employee->id)->where('status', 'active')->count());
    }
}
