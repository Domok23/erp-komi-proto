<?php

namespace Tests\Feature;

use App\Exceptions\HrHireException;
use App\Models\Company;
use App\Models\HrCandidate;
use App\Models\HrEmployee;
use App\Models\User;
use App\Services\HireCandidateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrHireCandidateTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Komi Test',
            'code' => 'KOMI',
            'address' => 'Jakarta',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    private function makeCandidate(array $overrides = []): HrCandidate
    {
        $candidate = HrCandidate::create(array_merge([
            'company_id' => $this->company->id,
            'name' => 'Budi Santoso',
            'nik' => '3174010101010001',
            'status' => 'checklist',
            'created_by' => $this->user->id,
        ], $overrides));

        $candidate->seedDefaultChecklist();

        return $candidate->fresh(['checklistItems']);
    }

    public function test_hire_blocked_when_required_checklist_incomplete(): void
    {
        $candidate = $this->makeCandidate();

        $this->expectException(HrHireException::class);
        $this->expectExceptionMessage('required checklist');

        HireCandidateService::hire($candidate, [
            'employee_number' => 'EMP-001',
            'join_date' => '2026-07-16',
            'override' => false,
        ], $this->user->id);
    }

    public function test_hire_succeeds_when_checklist_done(): void
    {
        $candidate = $this->makeCandidate();
        $candidate->checklistItems()->update(['status' => 'done']);

        $employee = HireCandidateService::hire($candidate, [
            'employee_number' => 'EMP-001',
            'join_date' => '2026-07-16',
            'contract' => [
                'type' => 'pkwt',
                'start_date' => '2026-07-16',
                'end_date' => '2027-07-15',
            ],
        ], $this->user->id);

        $this->assertSame('EMP-001', $employee->employee_number);
        $this->assertSame('Budi Santoso', $employee->name);
        $this->assertSame($candidate->id, $employee->candidate_id);
        $this->assertSame('hired', $candidate->fresh()->status);
        $this->assertNotNull($candidate->fresh()->hired_at);
        $this->assertDatabaseHas('hr_employment_contracts', [
            'employee_id' => $employee->id,
            'type' => 'pkwt',
            'status' => 'active',
        ]);
    }

    public function test_hire_override_requires_reason_and_audits(): void
    {
        $candidate = $this->makeCandidate();

        $employee = HireCandidateService::hire($candidate, [
            'employee_number' => 'EMP-002',
            'join_date' => '2026-07-16',
            'override' => true,
            'override_reason' => 'MCU scheduled next week',
        ], $this->user->id);

        $this->assertNotNull($employee->id);
        $fresh = $candidate->fresh();
        $this->assertSame('MCU scheduled next week', $fresh->hire_override_reason);
        $this->assertSame($this->user->id, $fresh->hire_override_by);
        $this->assertNotNull($fresh->hire_override_at);
    }

    public function test_cannot_double_hire(): void
    {
        $candidate = $this->makeCandidate();
        $candidate->checklistItems()->update(['status' => 'done']);

        HireCandidateService::hire($candidate, [
            'employee_number' => 'EMP-003',
            'join_date' => '2026-07-16',
        ], $this->user->id);

        $this->expectException(HrHireException::class);
        $this->expectExceptionMessage('already hired');

        HireCandidateService::hire($candidate->fresh(), [
            'employee_number' => 'EMP-004',
            'join_date' => '2026-07-16',
        ], $this->user->id);
    }

    public function test_duplicate_employee_number_rejected(): void
    {
        $c1 = $this->makeCandidate(['nik' => '111']);
        $c1->checklistItems()->update(['status' => 'done']);
        HireCandidateService::hire($c1, [
            'employee_number' => 'EMP-DUP',
            'join_date' => '2026-07-16',
        ], $this->user->id);

        $c2 = $this->makeCandidate(['name' => 'Ani', 'nik' => '222']);
        $c2->checklistItems()->update(['status' => 'done']);

        $this->expectException(HrHireException::class);
        $this->expectExceptionMessage('employee_number');

        HireCandidateService::hire($c2, [
            'employee_number' => 'EMP-DUP',
            'join_date' => '2026-07-16',
        ], $this->user->id);
    }

    public function test_duplicate_nik_rejected_on_hire(): void
    {
        HrEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-EXISTING',
            'name' => 'Existing Emp',
            'nik' => '999888777',
            'status' => 'active',
            'join_date' => '2026-01-01',
            'created_by' => $this->user->id,
        ]);

        $candidate = $this->makeCandidate(['nik' => '999888777']);
        $candidate->checklistItems()->update(['status' => 'done']);

        $this->expectException(HrHireException::class);
        $this->expectExceptionMessage('NIK');

        HireCandidateService::hire($candidate, [
            'employee_number' => 'EMP-NEW',
            'join_date' => '2026-07-16',
        ], $this->user->id);
    }
}
