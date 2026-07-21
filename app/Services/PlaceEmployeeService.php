<?php

namespace App\Services;

use App\Exceptions\HrPlacementException;
use App\Models\HrEmployee;
use App\Models\HrEmployeePlacement;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlaceEmployeeService
{
    public static function place(
        HrEmployee $employee,
        Project $project,
        string|\DateTimeInterface $startDate,
        int $userId
    ): HrEmployeePlacement {
        if ($employee->status !== 'active') {
            throw new HrPlacementException('Cannot place inactive employee');
        }

        if ((int) $project->company_id !== (int) $employee->company_id) {
            throw new HrPlacementException('Project company must match employee company');
        }

        return DB::transaction(function () use ($employee, $project, $startDate, $userId) {
            $start = Carbon::parse($startDate)->toDateString();

            HrEmployeePlacement::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'ended',
                    'end_date' => $start,
                    'updated_by' => $userId,
                ]);

            return HrEmployeePlacement::create([
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'start_date' => $start,
                'end_date' => null,
                'status' => 'active',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    public static function deactivate(HrEmployee $employee, int $userId): HrEmployee
    {
        return DB::transaction(function () use ($employee, $userId) {
            HrEmployeePlacement::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'ended',
                    'end_date' => Carbon::now()->toDateString(),
                    'updated_by' => $userId,
                ]);

            $employee->update([
                'status' => 'inactive',
                'updated_by' => $userId,
            ]);

            return $employee->fresh();
        });
    }
}
