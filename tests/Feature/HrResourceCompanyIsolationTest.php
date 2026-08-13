<?php

namespace Tests\Feature;

use App\Filament\Resources\HrLeaveRequestResource\Pages\ListHrLeaveRequests;
use App\Filament\Resources\HrOvertimeRecordResource\Pages\ListHrOvertimeRecords;
use App\Models\Company;
use App\Models\HrEmployee;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use App\Models\HrOvertimeRecord;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrResourceCompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;

    private Company $companyB;

    private HrLeaveRequest $leaveRequestA;

    private HrLeaveRequest $leaveRequestB;

    private HrOvertimeRecord $overtimeA;

    private HrOvertimeRecord $overtimeB;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->companyA = Company::create([
            'name' => 'Company A',
            'code' => 'COMA',
            'address' => 'Jakarta',
        ]);

        $this->companyB = Company::create([
            'name' => 'Company B',
            'code' => 'COMB',
            'address' => 'Bandung',
        ]);

        CompanyContext::setCompany($this->companyA);

        $employeeA = HrEmployee::create([
            'company_id' => $this->companyA->id,
            'employee_number' => 'EMP-A1',
            'name' => 'Alice',
            'status' => 'active',
            'join_date' => '2026-01-01',
            'created_by' => $user->id,
        ]);

        $employeeB = HrEmployee::create([
            'company_id' => $this->companyB->id,
            'employee_number' => 'EMP-B1',
            'name' => 'Bob',
            'status' => 'active',
            'join_date' => '2026-01-01',
            'created_by' => $user->id,
        ]);

        $leaveTypeA = HrLeaveType::create([
            'company_id' => $this->companyA->id,
            'code' => 'ANNUAL',
            'name' => 'Annual Leave',
            'default_quota_days' => 12,
        ]);

        $leaveTypeB = HrLeaveType::create([
            'company_id' => $this->companyB->id,
            'code' => 'ANNUAL',
            'name' => 'Annual Leave',
            'default_quota_days' => 12,
        ]);

        $this->leaveRequestA = HrLeaveRequest::create([
            'employee_id' => $employeeA->id,
            'leave_type_id' => $leaveTypeA->id,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-21',
            'status' => 'pending',
            'source' => 'hrd',
        ]);

        $this->leaveRequestB = HrLeaveRequest::create([
            'employee_id' => $employeeB->id,
            'leave_type_id' => $leaveTypeB->id,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-21',
            'status' => 'pending',
            'source' => 'hrd',
        ]);

        $this->overtimeA = HrOvertimeRecord::create([
            'employee_id' => $employeeA->id,
            'date' => '2026-07-15',
            'hours' => 2,
            'status' => 'pending',
        ]);

        $this->overtimeB = HrOvertimeRecord::create([
            'employee_id' => $employeeB->id,
            'date' => '2026-07-15',
            'hours' => 3,
            'status' => 'pending',
        ]);
    }

    public function test_leave_request_list_only_shows_current_company_records(): void
    {
        Livewire::test(ListHrLeaveRequests::class)
            ->assertCanSeeTableRecords([$this->leaveRequestA])
            ->assertCanNotSeeTableRecords([$this->leaveRequestB]);
    }

    public function test_overtime_list_only_shows_current_company_records(): void
    {
        Livewire::test(ListHrOvertimeRecords::class)
            ->assertCanSeeTableRecords([$this->overtimeA])
            ->assertCanNotSeeTableRecords([$this->overtimeB]);
    }
}
