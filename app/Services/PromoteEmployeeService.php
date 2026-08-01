<?php

namespace App\Services;

use App\Models\HrEmployee;
use App\Models\HrPositionHistory;
use Illuminate\Support\Facades\DB;

class PromoteEmployeeService
{
    /**
     * @param  array{
     *   to_department_id?: int|null,
     *   to_position_id?: int|null,
     *   type: string,
     *   effective_date: string,
     *   reason?: string|null
     * }  $data
     */
    public static function apply(HrEmployee $employee, array $data, int $userId): HrPositionHistory
    {
        return DB::transaction(function () use ($employee, $data, $userId) {
            $history = HrPositionHistory::create([
                'employee_id' => $employee->id,
                'from_department_id' => $employee->department_id,
                'from_position_id' => $employee->position_id,
                'to_department_id' => $data['to_department_id'] ?? null,
                'to_position_id' => $data['to_position_id'] ?? null,
                'type' => $data['type'],
                'effective_date' => $data['effective_date'],
                'reason' => $data['reason'] ?? null,
                'created_by' => $userId,
            ]);

            $employee->update([
                'department_id' => $data['to_department_id'] ?? $employee->department_id,
                'position_id' => $data['to_position_id'] ?? $employee->position_id,
                'updated_by' => $userId,
            ]);

            return $history;
        });
    }
}
