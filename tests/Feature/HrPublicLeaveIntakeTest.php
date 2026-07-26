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

    public function test_submit_with_attachment_saves_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->create('surat_dokter.pdf', 100, 'application/pdf');

        $response = $this->post("/leave-request/{$this->company->code}/submit", [
            'employee_number' => 'EMP-700',
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'reason' => 'Sakit demam',
            'attachment' => $file,
        ]);

        $response->assertRedirect();

        $leaveRequest = \App\Models\HrLeaveRequest::where('employee_id', $this->employee->id)->latest('id')->first();
        $this->assertNotNull($leaveRequest->file_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($leaveRequest->file_path);

        $attachmentResponse = $this->get("/leave-request/attachment/{$leaveRequest->id}");
        $attachmentResponse->assertOk();
    }

    public function test_sick_leave_requires_attachment(): void
    {
        $sickLeaveType = HrLeaveType::create([
            'company_id' => $this->company->id,
            'code' => 'SICK',
            'name' => 'Cuti Sakit',
            'is_sick_type' => true,
            'default_quota_days' => 10,
        ]);

        $response = $this->post("/leave-request/{$this->company->code}/submit", [
            'employee_number' => 'EMP-700',
            'leave_type_id' => $sickLeaveType->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'reason' => 'Sakit flu',
        ]);

        $response->assertSessionHasErrors('attachment');
    }

    public function test_overlapping_dates_returns_validation_error(): void
    {
        $this->post("/leave-request/{$this->company->code}/submit", [
            'employee_number' => 'EMP-700',
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-15',
            'reason' => 'Liburan',
        ]);

        $response = $this->post("/leave-request/{$this->company->code}/submit", [
            'employee_number' => 'EMP-700',
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-18',
            'reason' => 'Liburan susulan',
        ]);

        $response->assertSessionHasErrors('start_date');
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

    public function test_get_lookup_url_redirects_to_lookup_page(): void
    {
        $response = $this->get("/leave-request/{$this->company->code}/lookup");
        $response->assertRedirect("/leave-request/{$this->company->code}");
    }
}
