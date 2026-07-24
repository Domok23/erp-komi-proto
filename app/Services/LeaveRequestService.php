<?php

namespace App\Services;

use App\Exceptions\HrLeaveRequestException;
use App\Models\HrAttendance;
use App\Models\HrEmployee;
use App\Models\HrLeaveBalance;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    /**
     * @param  array{
     *   leave_type_id: int,
     *   start_date: string,
     *   end_date: string,
     *   reason?: string|null,
     *   file_path?: string|null
     * }  $data
     */
    public static function submit(HrEmployee $employee, array $data, string $source = 'hrd', ?int $userId = null): HrLeaveRequest
    {
        return HrLeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
            'file_path' => $data['file_path'] ?? null,
            'status' => 'pending',
            'source' => $source,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public static function approve(HrLeaveRequest $request, int $userId): HrLeaveRequest
    {
        if ($request->status !== 'pending') {
            throw new HrLeaveRequestException('Only pending requests can be approved');
        }

        return DB::transaction(function () use ($request, $userId) {
            $leaveType = HrLeaveType::findOrFail($request->leave_type_id);
            $attendanceStatus = $leaveType->is_sick_type ? 'sick' : 'leave';
            $workingDays = 0;

            foreach ($request->dateRange() as $dateString) {
                $dateObj = Carbon::parse($dateString);
                if (! $dateObj->isWeekend()) {
                    $workingDays++;
                    HrAttendance::updateOrCreate(
                        ['employee_id' => $request->employee_id, 'date' => $dateString],
                        [
                            'company_id' => $request->employee->company_id,
                            'status' => $attendanceStatus,
                            'updated_by' => $userId,
                        ]
                    );
                }
            }

            if ($leaveType->isQuotaBased() && $workingDays > 0) {
                $balance = HrLeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $request->start_date->year,
                    ],
                    ['quota_days' => $leaveType->default_quota_days, 'used_days' => 0]
                );

                $balance->increment('used_days', $workingDays);
            }

            $request->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => Carbon::now(),
                'updated_by' => $userId,
            ]);

            return $request->fresh();
        });
    }

    public static function reject(HrLeaveRequest $request, string $reason, int $userId): HrLeaveRequest
    {
        if ($request->status !== 'pending') {
            throw new HrLeaveRequestException('Only pending requests can be rejected');
        }

        if (blank($reason)) {
            throw new HrLeaveRequestException('rejected_reason is required');
        }

        $request->update([
            'status' => 'rejected',
            'rejected_reason' => $reason,
            'updated_by' => $userId,
        ]);

        return $request->fresh();
    }

    public static function findActiveEmployeeByNumber(int $companyId, string $employeeNumber): ?HrEmployee
    {
        return HrEmployee::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->where('employee_number', $employeeNumber)
            ->where('status', 'active')
            ->first();
    }
}
