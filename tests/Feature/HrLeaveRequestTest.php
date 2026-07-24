<?php

namespace Tests\Feature;

use App\Exceptions\HrLeaveRequestException;
use App\Models\Company;
use App\Models\HrEmployee;
use App\Models\HrLeaveBalance;
use App\Models\HrLeaveType;
use App\Models\User;
use App\Services\LeaveRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrLeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private HrEmployee $employee;

    private HrLeaveType $quotaType;

    private HrLeaveType $noQuotaType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Komi Test',
            'code' => 'KOMI',
            'address' => 'Jakarta',
        ]);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        $this->employee = HrEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-500',
            'name' => 'Sari',
            'status' => 'active',
            'join_date' => '2025-01-01',
            'created_by' => $this->user->id,
        ]);

        $this->quotaType = HrLeaveType::create([
            'company_id' => $this->company->id,
            'code' => 'ANNUAL',
            'name' => 'Cuti Tahunan',
            'default_quota_days' => 12,
            'requires_document' => false,
        ]);

        $this->noQuotaType = HrLeaveType::create([
            'company_id' => $this->company->id,
            'code' => 'SICK',
            'name' => 'Sakit',
            'default_quota_days' => null,
            'requires_document' => true,
            'is_sick_type' => true,
        ]);

        HrLeaveBalance::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->quotaType->id,
            'year' => 2026,
            'quota_days' => 12,
            'used_days' => 0,
        ]);
    }

    public function test_submit_creates_pending_request(): void
    {
        $request = LeaveRequestService::submit($this->employee, [
            'leave_type_id' => $this->quotaType->id,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-21',
            'reason' => 'Liburan',
        ], 'hrd', $this->user->id);

        $this->assertSame('pending', $request->status);
        $this->assertSame('hrd', $request->source);
    }

    public function test_approve_deducts_balance_and_fills_attendance(): void
    {
        $request = LeaveRequestService::submit($this->employee, [
            'leave_type_id' => $this->quotaType->id,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-21',
            'reason' => 'Liburan',
        ], 'public_intake');

        LeaveRequestService::approve($request, $this->user->id);

        $balance = HrLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->quotaType->id)
            ->where('year', 2026)
            ->first();

        $this->assertSame(2, $balance->used_days);
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertDatabaseHas('hr_attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2026-07-20 00:00:00',
            'status' => 'leave',
        ]);
        $this->assertDatabaseHas('hr_attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2026-07-21 00:00:00',
            'status' => 'leave',
        ]);
    }

    public function test_approve_sick_type_fills_attendance_as_sick_without_balance(): void
    {
        $request = LeaveRequestService::submit($this->employee, [
            'leave_type_id' => $this->noQuotaType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-03',
            'reason' => 'Demam',
            'file_path' => 'letters/doctor-note.pdf',
        ], 'hrd', $this->user->id);

        LeaveRequestService::approve($request, $this->user->id);

        $this->assertDatabaseHas('hr_attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2026-08-03 00:00:00',
            'status' => 'sick',
        ]);
        $this->assertSame(0, HrLeaveBalance::where('leave_type_id', $this->noQuotaType->id)->count());
    }

    public function test_approve_custom_code_sick_type_with_flag_fills_attendance_as_sick(): void
    {
        $customSickType = HrLeaveType::create([
            'company_id' => $this->company->id,
            'code' => 'SAKIT',
            'name' => 'Cuti Sakit',
            'default_quota_days' => null,
            'requires_document' => true,
            'is_sick_type' => true,
        ]);

        $request = LeaveRequestService::submit($this->employee, [
            'leave_type_id' => $customSickType->id,
            'start_date' => '2026-08-05',
            'end_date' => '2026-08-05',
            'reason' => 'Flu',
        ], 'hrd', $this->user->id);

        LeaveRequestService::approve($request, $this->user->id);

        $this->assertDatabaseHas('hr_attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2026-08-05 00:00:00',
            'status' => 'sick',
        ]);
    }

    public function test_reject_does_not_touch_balance_or_attendance(): void
    {
        $request = LeaveRequestService::submit($this->employee, [
            'leave_type_id' => $this->quotaType->id,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-20',
            'reason' => 'Liburan',
        ], 'hrd', $this->user->id);

        LeaveRequestService::reject($request, 'Kuota tidak cukup bulan ini', $this->user->id);

        $fresh = $request->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Kuota tidak cukup bulan ini', $fresh->rejected_reason);
        $this->assertDatabaseMissing('hr_attendances', ['employee_id' => $this->employee->id]);

        $balance = HrLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->quotaType->id)
            ->first();
        $this->assertSame(0, $balance->used_days);
    }

    public function test_reject_requires_reason(): void
    {
        $request = LeaveRequestService::submit($this->employee, [
            'leave_type_id' => $this->quotaType->id,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-20',
        ], 'hrd', $this->user->id);

        $this->expectException(HrLeaveRequestException::class);

        LeaveRequestService::reject($request, '', $this->user->id);
    }

    public function test_find_active_employee_by_number(): void
    {
        $found = LeaveRequestService::findActiveEmployeeByNumber($this->company->id, 'EMP-500');
        $this->assertNotNull($found);
        $this->assertSame($this->employee->id, $found->id);

        $this->assertNull(LeaveRequestService::findActiveEmployeeByNumber($this->company->id, 'NOPE'));

        $this->employee->update(['status' => 'inactive']);
        $this->assertNull(LeaveRequestService::findActiveEmployeeByNumber($this->company->id, 'EMP-500'));
    }

    public function test_approve_leave_excludes_weekends_from_quota_deduction(): void
    {
        // 2026-07-31 is Friday, 2026-08-03 is Monday (span of 4 calendar days: Fri, Sat, Sun, Mon)
        $request = LeaveRequestService::submit($this->employee, [
            'leave_type_id' => $this->quotaType->id,
            'start_date' => '2026-07-31',
            'end_date' => '2026-08-03',
            'reason' => 'Liburan',
        ], 'hrd', $this->user->id);

        LeaveRequestService::approve($request, $this->user->id);

        // Balance should increment by 2 working days (Friday & Monday only), not 4
        $balance = HrLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->quotaType->id)
            ->first();

        $this->assertSame(2, $balance->used_days);
    }

    public function test_delete_approved_leave_restores_quota_and_removes_attendance(): void
    {
        $request = LeaveRequestService::submit($this->employee, [
            'leave_type_id' => $this->quotaType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-04',
            'reason' => 'Liburan',
        ], 'hrd', $this->user->id);

        LeaveRequestService::approve($request, $this->user->id);

        $balanceBefore = HrLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->quotaType->id)
            ->first();
        $this->assertSame(2, $balanceBefore->used_days);
        $this->assertDatabaseHas('hr_attendances', ['employee_id' => $this->employee->id, 'date' => '2026-08-03 00:00:00']);

        LeaveRequestService::deleteRequest($request);

        $balanceAfter = HrLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->quotaType->id)
            ->first();
        $this->assertSame(0, $balanceAfter->used_days);
        $this->assertDatabaseMissing('hr_attendances', ['employee_id' => $this->employee->id, 'date' => '2026-08-03 00:00:00']);
    }

    public function test_approve_leave_on_weekend_only_throws_exception(): void
    {
        // 2026-08-01 is Saturday, 2026-08-02 is Sunday
        $request = LeaveRequestService::submit($this->employee, [
            'leave_type_id' => $this->quotaType->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'reason' => 'Weekend leave',
        ], 'hrd', $this->user->id);

        $this->expectException(\App\Exceptions\HrLeaveRequestException::class);
        $this->expectExceptionMessage('Cannot approve leave request with 0 working days');

        LeaveRequestService::approve($request, $this->user->id);
    }

    public function test_approve_leave_exceeding_quota_throws_exception(): void
    {
        // Set quota to 1 day on both LeaveType and existing LeaveBalance
        $this->quotaType->update(['default_quota_days' => 1]);
        HrLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->quotaType->id)
            ->update(['quota_days' => 1]);

        $request = LeaveRequestService::submit($this->employee, [
            'leave_type_id' => $this->quotaType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-05', // 3 working days
            'reason' => 'Long vacation',
        ], 'hrd', $this->user->id);

        $this->expectException(\App\Exceptions\HrLeaveRequestException::class);
        $this->expectExceptionMessage('Insufficient leave quota');

        LeaveRequestService::approve($request, $this->user->id);
    }
}
