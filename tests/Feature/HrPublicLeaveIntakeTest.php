<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HrEmployee;
use App\Models\HrLeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPublicLeaveIntakeTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private HrEmployee $employee;

    private HrLeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Komi', 'code' => 'KOMI', 'address' => 'Jakarta']);

        $this->employee = HrEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-700',
            'name' => 'Wati',
            'status' => 'active',
            'join_date' => '2025-01-01',
        ]);

        $this->leaveType = HrLeaveType::create([
            'company_id' => $this->company->id,
            'code' => 'ANNUAL',
            'name' => 'Cuti Tahunan',
            'default_quota_days' => 12,
        ]);
    }

    public function test_lookup_page_loads(): void
    {
        $this->get("/leave-request/{$this->company->code}")->assertOk();
    }

    public function test_lookup_with_valid_employee_number_shows_form(): void
    {
        $response = $this->post("/leave-request/{$this->company->code}/lookup", [
            'employee_number' => 'EMP-700',
        ]);

        $response->assertOk();
        $response->assertSee('Wati');
    }

    public function test_lookup_with_invalid_employee_number_shows_error(): void
    {
        $response = $this->post("/leave-request/{$this->company->code}/lookup", [
            'employee_number' => 'NOPE',
        ]);

        $response->assertSessionHasErrors('employee_number');
    }

    public function test_lookup_with_inactive_employee_shows_error(): void
    {
        $this->employee->update(['status' => 'inactive']);

        $response = $this->post("/leave-request/{$this->company->code}/lookup", [
            'employee_number' => 'EMP-700',
        ]);

        $response->assertSessionHasErrors('employee_number');
    }

    public function test_submit_creates_pending_leave_request_with_public_source(): void
    {
        $response = $this->post("/leave-request/{$this->company->code}/submit", [
            'employee_number' => 'EMP-700',
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'reason' => 'Acara keluarga',
        ]);

        $response->assertRedirect("/leave-request/{$this->company->code}?employee_number=EMP-700");
        $response->assertSessionHas('status', 'Pengajuan cuti berhasil dikirim.');

        $this->assertDatabaseHas('hr_leave_requests', [
            'employee_id' => $this->employee->id,
            'status' => 'pending',
            'source' => 'public_intake',
        ]);
    }

    public function test_lookup_page_with_employee_number_query_shows_employee_view(): void
    {
        $response = $this->get("/leave-request/{$this->company->code}?employee_number=EMP-700");

        $response->assertOk();
        $response->assertSee('Wati');
        $response->assertSee('Ajukan Cuti Baru');
    }

    public function test_rejected_leave_request_displays_rejection_reason(): void
    {
        \App\Models\HrLeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'reason' => 'Acara pribadi',
            'status' => 'rejected',
            'rejected_reason' => 'Quota tidak mencukupi untuk bulan ini.',
            'source' => 'public_intake',
        ]);

        $response = $this->get("/leave-request/{$this->company->code}?employee_number=EMP-700");

        $response->assertOk();
        $response->assertSee('Ditolak');
        $response->assertSee('Quota tidak mencukupi untuk bulan ini.');
    }

    public function test_invalid_company_code_returns_404(): void
    {
        $this->get('/leave-request/does-not-exist')->assertNotFound();
    }

    public function test_submit_rejects_leave_type_from_another_company(): void
    {
        $otherCompany = Company::create(['name' => 'Other Co', 'code' => 'OTHER', 'address' => 'Bandung']);

        $otherLeaveType = HrLeaveType::create([
            'company_id' => $otherCompany->id,
            'code' => 'ANNUAL',
            'name' => 'Cuti Tahunan Other',
            'default_quota_days' => 12,
        ]);

        $response = $this->post("/leave-request/{$this->company->code}/submit", [
            'employee_number' => 'EMP-700',
            'leave_type_id' => $otherLeaveType->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'reason' => 'Acara keluarga',
        ]);

        $response->assertSessionHasErrors('leave_type_id');

        $this->assertDatabaseMissing('hr_leave_requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $otherLeaveType->id,
        ]);
    }
}
