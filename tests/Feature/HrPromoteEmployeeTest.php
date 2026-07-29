<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrPosition;
use App\Models\User;
use App\Services\PromoteEmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPromoteEmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_updates_employee_and_records_history(): void
    {
        $company = Company::create(['name' => 'Komi', 'code' => 'K1', 'address' => 'Jakarta']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $staffPosition = HrPosition::create(['company_id' => $company->id, 'code' => 'STAFF', 'name' => 'Staff']);
        $supervisorPosition = HrPosition::create(['company_id' => $company->id, 'code' => 'SPV', 'name' => 'Supervisor']);
        $department = HrDepartment::create(['company_id' => $company->id, 'code' => 'PROD', 'name' => 'Production']);

        $employee = HrEmployee::create([
            'company_id' => $company->id,
            'employee_number' => 'EMP-900',
            'name' => 'Dedi',
            'status' => 'active',
            'join_date' => '2024-01-01',
            'position_id' => $staffPosition->id,
            'department_id' => $department->id,
            'created_by' => $user->id,
        ]);

        $history = PromoteEmployeeService::apply($employee, [
            'to_department_id' => $department->id,
            'to_position_id' => $supervisorPosition->id,
            'type' => 'promotion',
            'effective_date' => '2026-08-01',
            'reason' => 'Kinerja baik',
        ], $user->id);

        $this->assertSame($staffPosition->id, $history->from_position_id);
        $this->assertSame($supervisorPosition->id, $history->to_position_id);
        $this->assertSame('promotion', $history->type);
        $this->assertSame($supervisorPosition->id, $employee->fresh()->position_id);
    }
}
