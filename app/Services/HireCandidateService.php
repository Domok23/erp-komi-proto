<?php

namespace App\Services;

use App\Exceptions\HrHireException;
use App\Models\HrCandidate;
use App\Models\HrEmployee;
use App\Models\HrEmploymentContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HireCandidateService
{
    /**
     * @param  array{
     *   employee_number: string,
     *   department_id?: int|null,
     *   position_id?: int|null,
     *   join_date: string|\DateTimeInterface,
     *   override?: bool,
     *   override_reason?: string|null,
     *   contract?: array{
     *     type: string,
     *     start_date: string,
     *     end_date?: string|null,
     *     file_path?: string|null,
     *     notes?: string|null
     *   }|null
     * }  $data
     */
    public static function hire(HrCandidate $candidate, array $data, int $userId): HrEmployee
    {
        if ($candidate->status === 'hired') {
            throw new HrHireException('Candidate already hired');
        }

        $override = (bool) ($data['override'] ?? false);
        $overrideReason = $data['override_reason'] ?? null;

        if ($override && blank($overrideReason)) {
            throw new HrHireException('override_reason is required when overriding required checklist');
        }

        $candidate->load('checklistItems');
        $unsatisfied = $candidate->checklistItems
            ->filter(fn ($item) => $item->is_required && ! $item->isSatisfied())
            ->values();

        if ($unsatisfied->isNotEmpty() && ! $override) {
            $labels = $unsatisfied->pluck('label')->implode(', ');
            throw new HrHireException("Incomplete required checklist: {$labels}");
        }

        $employeeNumber = $data['employee_number'] ?? null;
        if (blank($employeeNumber)) {
            throw new HrHireException('employee_number is required');
        }

        $exists = HrEmployee::withoutCompanyScope()
            ->where('company_id', $candidate->company_id)
            ->where('employee_number', $employeeNumber)
            ->exists();

        if ($exists) {
            throw new HrHireException('employee_number already exists for this company');
        }

        if (filled($candidate->nik)) {
            $nikExists = HrEmployee::withoutCompanyScope()
                ->where('company_id', $candidate->company_id)
                ->where('nik', $candidate->nik)
                ->exists();

            if ($nikExists) {
                throw new HrHireException("The NIK ({$candidate->nik}) has already been registered for an employee in this company.");
            }
        }

        try {
            return DB::transaction(function () use ($candidate, $data, $userId, $override, $overrideReason, $employeeNumber) {
                $employee = HrEmployee::create([
                    'company_id' => $candidate->company_id,
                    'candidate_id' => $candidate->id,
                    'employee_number' => $employeeNumber,
                    'name' => $candidate->name,
                    'nik' => $candidate->nik,
                    'phone' => $candidate->phone,
                    'email' => $candidate->email,
                    'address' => $candidate->address,
                    'department_id' => $data['department_id'] ?? null,
                    'position_id' => $data['position_id'] ?? null,
                    'join_date' => $data['join_date'],
                    'status' => 'active',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                if (! empty($data['contract']['type'] ?? null)) {
                    HrEmploymentContract::create([
                        'employee_id' => $employee->id,
                        'type' => $data['contract']['type'],
                        'start_date' => $data['contract']['start_date'],
                        'end_date' => $data['contract']['end_date'] ?? null,
                        'file_path' => $data['contract']['file_path'] ?? null,
                        'status' => 'active',
                        'notes' => $data['contract']['notes'] ?? null,
                    ]);
                }

                $candidate->update([
                    'status' => 'hired',
                    'hired_at' => Carbon::now(),
                    'hire_override_reason' => $override ? $overrideReason : null,
                    'hire_override_by' => $override ? $userId : null,
                    'hire_override_at' => $override ? Carbon::now() : null,
                    'updated_by' => $userId,
                ]);

                return $employee;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'hr_employees_company_id_nik_unique')) {
                throw new HrHireException("The NIK ({$candidate->nik}) has already been registered for an employee in this company.");
            }
            if (str_contains($e->getMessage(), 'hr_employees_company_id_employee_number_unique')) {
                throw new HrHireException('employee_number already exists for this company');
            }

            throw $e;
        }
    }
}
