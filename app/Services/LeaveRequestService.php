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
        $hasOverlap = HrLeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($data) {
                $query->where('start_date', '<=', $data['end_date'])
                      ->where('end_date', '>=', $data['start_date']);
            })
            ->exists();

        if ($hasOverlap) {
            throw new HrLeaveRequestException('Anda sudah memiliki pengajuan cuti aktif (Pending/Disetujui) pada rentang tanggal tersebut.');
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        $workingDays = 0;
        for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
            if (! $d->isWeekend()) {
                $workingDays++;
            }
        }

        if ($workingDays === 0) {
            throw new HrLeaveRequestException('Pengajuan cuti harus mencakup minimal 1 hari kerja (tidak bisa hanya akhir pekan).');
        }

        $leaveType = HrLeaveType::findOrFail($data['leave_type_id']);

        if ($leaveType->isQuotaBased()) {
            $balance = HrLeaveBalance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $startDate->year,
                ],
                ['quota_days' => $leaveType->default_quota_days, 'used_days' => 0]
            );

            $pendingRequests = HrLeaveRequest::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('status', 'pending')
                ->whereYear('start_date', $startDate->year)
                ->get();

            $pendingDays = 0;
            foreach ($pendingRequests as $pReq) {
                foreach ($pReq->dateRange() as $dStr) {
                    if (! Carbon::parse($dStr)->isWeekend()) {
                        $pendingDays++;
                    }
                }
            }

            $remainingQuota = $balance->quota_days - ($balance->used_days + $pendingDays);

            if ($workingDays > $remainingQuota) {
                $remainingText = max(0, $remainingQuota);
                throw new HrLeaveRequestException("Sisa kuota {$leaveType->name} Anda tidak mencukupi (Sisa kuota: {$remainingText} hari, Pengajuan: {$workingDays} hari kerja).");
            }
        }

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

    public static function getLeaveBalances(HrEmployee $employee, int $year): array
    {
        $leaveTypes = HrLeaveType::withoutCompanyScope()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->get();

        $balances = [];
        foreach ($leaveTypes as $type) {
            if ($type->isQuotaBased()) {
                $balance = HrLeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                    ],
                    ['quota_days' => $type->default_quota_days, 'used_days' => 0]
                );

                $pendingDays = 0;
                $pendingRequests = HrLeaveRequest::where('employee_id', $employee->id)
                    ->where('leave_type_id', $type->id)
                    ->where('status', 'pending')
                    ->whereYear('start_date', $year)
                    ->get();

                foreach ($pendingRequests as $pReq) {
                    foreach ($pReq->dateRange() as $dStr) {
                        if (! Carbon::parse($dStr)->isWeekend()) {
                            $pendingDays++;
                        }
                    }
                }

                $remaining = max(0, $balance->quota_days - ($balance->used_days + $pendingDays));
                $balances[$type->id] = [
                    'quota' => $balance->quota_days,
                    'used' => $balance->used_days,
                    'pending' => $pendingDays,
                    'remaining' => $remaining,
                ];
            } else {
                $balances[$type->id] = null;
            }
        }

        return $balances;
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

            if ($workingDays === 0) {
                throw new HrLeaveRequestException('Cannot approve leave request with 0 working days');
            }

            if ($leaveType->isQuotaBased()) {
                $balance = HrLeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $request->start_date->year,
                    ],
                    ['quota_days' => $leaveType->default_quota_days, 'used_days' => 0]
                );

                if (($balance->used_days + $workingDays) > $balance->quota_days) {
                    throw new HrLeaveRequestException('Insufficient leave quota');
                }

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

    public static function deleteRequest(HrLeaveRequest $request): void
    {
        DB::transaction(function () use ($request) {
            if ($request->status === 'approved') {
                $leaveType = HrLeaveType::find($request->leave_type_id);
                if ($leaveType) {
                    $attendanceStatus = $leaveType->is_sick_type ? 'sick' : 'leave';
                    $workingDays = 0;

                    foreach ($request->dateRange() as $dateString) {
                        $dateObj = Carbon::parse($dateString);
                        if (! $dateObj->isWeekend()) {
                            $workingDays++;
                        }

                        HrAttendance::where('employee_id', $request->employee_id)
                            ->whereDate('date', $dateString)
                            ->where('status', $attendanceStatus)
                            ->delete();
                    }

                    if ($leaveType->isQuotaBased() && $workingDays > 0) {
                        $balance = HrLeaveBalance::where('employee_id', $request->employee_id)
                            ->where('leave_type_id', $leaveType->id)
                            ->where('year', $request->start_date->year)
                            ->first();

                        if ($balance) {
                            $balance->decrement('used_days', min($balance->used_days, $workingDays));
                        }
                    }
                }
            }

            $request->delete();
        });
    }
}
