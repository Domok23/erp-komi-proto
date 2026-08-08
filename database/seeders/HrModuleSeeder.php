<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\HrAttendance;
use App\Models\HrCandidate;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrEmployeePlacement;
use App\Models\HrEmploymentContract;
use App\Models\HrHiringChecklistItem;
use App\Models\HrLeaveBalance;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use App\Models\HrOvertimeRecord;
use App\Models\HrPosition;
use App\Models\HrPositionHistory;
use App\Models\HrWarningLetter;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HrModuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $company = Company::query()->where('code', 'KEI')->firstOrFail();
            $userId = User::query()->where('company_id', $company->id)->value('id');
            $today = now()->startOfDay();

            $departments = collect([
                ['code' => 'HR', 'name' => 'Human Resources'],
                ['code' => 'PROD', 'name' => 'Production'],
                ['code' => 'FIN', 'name' => 'Finance'],
                ['code' => 'GA', 'name' => 'General Affairs'],
            ])->mapWithKeys(fn (array $department): array => [
                $department['code'] => HrDepartment::withoutCompanyScope()->updateOrCreate(
                    ['company_id' => $company->id, 'code' => $department['code']],
                    [...$department, 'company_id' => $company->id, 'is_active' => true],
                ),
            ]);

            $positions = collect([
                ['code' => 'HR-STAFF', 'name' => 'HR Staff', 'department' => 'HR'],
                ['code' => 'HR-SPV', 'name' => 'HR Supervisor', 'department' => 'HR'],
                ['code' => 'PROD-OPR', 'name' => 'Production Operator', 'department' => 'PROD'],
                ['code' => 'PROD-SPV', 'name' => 'Production Supervisor', 'department' => 'PROD'],
                ['code' => 'FIN-STAFF', 'name' => 'Finance Staff', 'department' => 'FIN'],
                ['code' => 'GA-MGR', 'name' => 'General Affairs Manager', 'department' => 'GA'],
            ])->mapWithKeys(function (array $position) use ($company, $departments): array {
                return [
                    $position['code'] => HrPosition::withoutCompanyScope()->updateOrCreate(
                        ['company_id' => $company->id, 'code' => $position['code']],
                        [
                            'company_id' => $company->id,
                            'department_id' => $departments[$position['department']]->id,
                            'code' => $position['code'],
                            'name' => $position['name'],
                            'is_active' => true,
                        ],
                    ),
                ];
            });

            $candidates = collect([
                [
                    'nik' => '3273010101010101',
                    'name' => 'Rizky Pratama',
                    'phone' => '081200000101',
                    'email' => 'rizky.pratama@example.test',
                    'status' => 'screening',
                    'source' => 'Jobstreet',
                    'notes' => 'Menunggu jadwal wawancara user.',
                    'checklist_status' => 'pending',
                ],
                [
                    'nik' => '3273010101010102',
                    'name' => 'Nadia Safitri',
                    'phone' => '081200000102',
                    'email' => 'nadia.safitri@example.test',
                    'status' => 'ready_to_hire',
                    'source' => 'Referral',
                    'notes' => 'Seluruh dokumen siap untuk proses hiring.',
                    'checklist_status' => 'done',
                ],
                [
                    'nik' => '3273010101010103',
                    'name' => 'Dimas Kurniawan',
                    'phone' => '081200000103',
                    'email' => 'dimas.kurniawan@example.test',
                    'status' => 'hired',
                    'source' => 'Walk-in',
                    'notes' => 'Berhasil di-hire sebagai HR Staff.',
                    'checklist_status' => 'done',
                ],
            ])->mapWithKeys(function (array $candidate) use ($company, $userId, $today): array {
                $model = HrCandidate::withoutCompanyScope()->updateOrCreate(
                    ['company_id' => $company->id, 'nik' => $candidate['nik']],
                    [
                        'company_id' => $company->id,
                        'name' => $candidate['name'],
                        'nik' => $candidate['nik'],
                        'phone' => $candidate['phone'],
                        'email' => $candidate['email'],
                        'address' => 'Bandung, Jawa Barat',
                        'source' => $candidate['source'],
                        'status' => $candidate['status'],
                        'notes' => $candidate['notes'],
                        'hired_at' => $candidate['status'] === 'hired' ? $today->copy()->subMonths(8) : null,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ],
                );

                foreach ([
                    ['type' => 'mcu', 'label' => 'Medical Check Up'],
                    ['type' => 'bank_account', 'label' => 'Bank Account'],
                ] as $item) {
                    HrHiringChecklistItem::updateOrCreate(
                        ['candidate_id' => $model->id, 'type' => $item['type']],
                        [...$item, 'candidate_id' => $model->id, 'is_required' => true, 'status' => $candidate['checklist_status']],
                    );
                }

                return [$candidate['nik'] => $model];
            });

            $employees = collect([
                ['number' => 'KEI-HR-001', 'name' => 'Dimas Kurniawan', 'nik' => '3273010101010103', 'department' => 'HR', 'position' => 'HR-STAFF', 'candidate' => '3273010101010103'],
                ['number' => 'KEI-HR-002', 'name' => 'Siti Rahmawati', 'nik' => '3273010101010104', 'department' => 'HR', 'position' => 'HR-SPV'],
                ['number' => 'KEI-PR-001', 'name' => 'Agus Setiawan', 'nik' => '3273010101010105', 'department' => 'PROD', 'position' => 'PROD-OPR'],
                ['number' => 'KEI-PR-002', 'name' => 'Wulan Permata', 'nik' => '3273010101010106', 'department' => 'PROD', 'position' => 'PROD-SPV'],
                ['number' => 'KEI-PR-003', 'name' => 'Bambang Saputra', 'nik' => '3273010101010107', 'department' => 'PROD', 'position' => 'PROD-OPR'],
                ['number' => 'KEI-FN-001', 'name' => 'Lina Marlina', 'nik' => '3273010101010108', 'department' => 'FIN', 'position' => 'FIN-STAFF'],
            ])->mapWithKeys(function (array $employee) use ($company, $userId, $today, $departments, $positions, $candidates): array {
                $model = HrEmployee::withoutCompanyScope()->updateOrCreate(
                    ['company_id' => $company->id, 'employee_number' => $employee['number']],
                    [
                        'company_id' => $company->id,
                        'candidate_id' => isset($employee['candidate']) ? $candidates[$employee['candidate']]->id : null,
                        'employee_number' => $employee['number'],
                        'name' => $employee['name'],
                        'nik' => $employee['nik'],
                        'phone' => '0812'.substr($employee['nik'], -8),
                        'email' => strtolower(str_replace(' ', '.', $employee['name'])).'@komi.test',
                        'address' => 'Bandung, Jawa Barat',
                        'department_id' => $departments[$employee['department']]->id,
                        'position_id' => $positions[$employee['position']]->id,
                        'join_date' => $today->copy()->subMonths(8),
                        'status' => 'active',
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ],
                );

                HrEmploymentContract::updateOrCreate(
                    ['employee_id' => $model->id, 'start_date' => $today->copy()->subMonths(8)->toDateString()],
                    [
                        'employee_id' => $model->id,
                        'type' => 'pkwt',
                        'start_date' => $today->copy()->subMonths(8)->toDateString(),
                        'end_date' => $today->copy()->addMonths(4)->toDateString(),
                        'status' => 'active',
                        'notes' => 'Kontrak PKWT demo HR.',
                    ],
                );

                return [$employee['number'] => $model];
            });

            $project = Project::query()->where('company_id', $company->id)->orderBy('id')->first();
            if ($project !== null) {
                foreach ($employees as $employee) {
                    HrEmployeePlacement::updateOrCreate(
                        ['employee_id' => $employee->id, 'project_id' => $project->id],
                        [
                            'employee_id' => $employee->id,
                            'project_id' => $project->id,
                            'start_date' => $today->copy()->subMonths(6)->toDateString(),
                            'status' => 'active',
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ],
                    );
                }
            }

            $leaveTypes = collect([
                ['code' => 'ANNUAL', 'name' => 'Cuti Tahunan', 'quota' => 12, 'requires_document' => false, 'is_sick_type' => false],
                ['code' => 'SPECIAL', 'name' => 'Cuti Khusus', 'quota' => 3, 'requires_document' => true, 'is_sick_type' => false],
                ['code' => 'SAKIT', 'name' => 'Cuti Sakit', 'quota' => null, 'requires_document' => true, 'is_sick_type' => true],
                ['code' => 'MATERNITY', 'name' => 'Cuti Melahirkan', 'quota' => 90, 'requires_document' => true, 'is_sick_type' => false],
                ['code' => 'UNPAID', 'name' => 'Cuti Tanpa Gaji', 'quota' => null, 'requires_document' => false, 'is_sick_type' => false],
            ])->mapWithKeys(function (array $leaveType) use ($company): array {
                return [
                    $leaveType['code'] => HrLeaveType::withoutCompanyScope()->updateOrCreate(
                        ['company_id' => $company->id, 'code' => $leaveType['code']],
                        [
                            'company_id' => $company->id,
                            'code' => $leaveType['code'],
                            'name' => $leaveType['name'],
                            'default_quota_days' => $leaveType['quota'],
                            'requires_document' => $leaveType['requires_document'],
                            'is_sick_type' => $leaveType['is_sick_type'],
                            'is_active' => true,
                        ],
                    ),
                ];
            });

            foreach ($employees as $employee) {
                foreach ($leaveTypes->filter(fn (HrLeaveType $type): bool => $type->default_quota_days !== null) as $leaveType) {
                    HrLeaveBalance::updateOrCreate(
                        ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => $today->year],
                        [
                            'employee_id' => $employee->id,
                            'leave_type_id' => $leaveType->id,
                            'year' => $today->year,
                            'quota_days' => $leaveType->default_quota_days,
                            'used_days' => $employee->employee_number === 'KEI-HR-001' && $leaveType->code === 'ANNUAL' ? 2 : 0,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ],
                    );
                }
            }

            $approved = $this->seedLeaveRequest($employees['KEI-HR-001'], $leaveTypes['ANNUAL'], $today->copy()->subDays(20), $today->copy()->subDays(19), 'approved', 'Liburan keluarga.', $userId);
            $this->seedLeaveRequest($employees['KEI-PR-001'], $leaveTypes['SAKIT'], $today->copy()->subDays(12), $today->copy()->subDays(11), 'rejected', 'Istirahat karena flu.', $userId);
            $this->seedLeaveRequest($employees['KEI-FN-001'], $leaveTypes['SPECIAL'], $today->copy()->addDays(7), $today->copy()->addDays(7), 'pending', 'Keperluan keluarga.', $userId);

            foreach ($approved->dateRange() as $date) {
                $date = Carbon::parse($date)->toDateString();
                $attendance = HrAttendance::withoutCompanyScope()
                    ->where('employee_id', $approved->employee_id)
                    ->whereDate('date', $date)
                    ->first() ?? new HrAttendance;

                $attendance->fill([
                    'company_id' => $company->id,
                    'employee_id' => $approved->employee_id,
                    'date' => $date,
                    'status' => 'leave',
                    'notes' => 'Cuti tahunan disetujui.',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])->save();
            }

            foreach ([
                ['employee' => 'KEI-PR-002', 'date' => $today->copy()->subDays(2), 'hours' => 3.5, 'reason' => 'Pengejaran target produksi.', 'status' => 'approved'],
                ['employee' => 'KEI-PR-003', 'date' => $today, 'hours' => 2, 'reason' => 'Stock opname.', 'status' => 'pending'],
                ['employee' => 'KEI-FN-001', 'date' => $today->copy()->subDays(3), 'hours' => 1.5, 'reason' => 'Permintaan lembur tidak sesuai kebutuhan.', 'status' => 'rejected'],
            ] as $overtime) {
                $record = HrOvertimeRecord::query()
                    ->where('employee_id', $employees[$overtime['employee']]->id)
                    ->whereDate('date', $overtime['date'])
                    ->where('reason', $overtime['reason'])
                    ->first() ?? new HrOvertimeRecord;

                $record->fill([
                    'employee_id' => $employees[$overtime['employee']]->id,
                    'date' => $overtime['date']->toDateString(),
                    'hours' => $overtime['hours'],
                    'reason' => $overtime['reason'],
                    'status' => $overtime['status'],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])->save();
            }

            HrWarningLetter::withoutCompanyScope()->updateOrCreate(
                ['company_id' => $company->id, 'letter_number' => 'SP-KEI-001'],
                [
                    'company_id' => $company->id,
                    'employee_id' => $employees['KEI-PR-003']->id,
                    'letter_number' => 'SP-KEI-001',
                    'issued_date' => $today->copy()->subDays(30)->toDateString(),
                    'reason' => 'Pelanggaran prosedur keselamatan kerja.',
                    'notes' => 'Surat peringatan pertama.',
                    'created_by' => $userId,
                ],
            );

            $historyEmployee = $employees['KEI-PR-003'];
            $this->seedPositionHistory(
                $historyEmployee,
                $today->copy()->subMonths(3),
                'promotion',
                $departments['PROD']->id,
                $positions['PROD-OPR']->id,
                $departments['PROD']->id,
                $positions['PROD-SPV']->id,
                'Kinerja produksi sangat baik.',
                $userId,
            );
            $this->seedPositionHistory(
                $historyEmployee,
                $today->copy()->subMonth(),
                'demotion',
                $departments['PROD']->id,
                $positions['PROD-SPV']->id,
                $departments['PROD']->id,
                $positions['PROD-OPR']->id,
                'Penyesuaian struktur tim produksi.',
                $userId,
            );
        });
    }

    private function seedLeaveRequest(
        HrEmployee $employee,
        HrLeaveType $leaveType,
        Carbon $startDate,
        Carbon $endDate,
        string $status,
        string $reason,
        ?int $userId,
    ): HrLeaveRequest {
        $request = HrLeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->first() ?? new HrLeaveRequest;

        $request->fill([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'reason' => $reason,
            'status' => $status,
            'source' => $status === 'pending' ? 'public_intake' : 'hrd',
            'approved_by' => $status === 'approved' ? $userId : null,
            'approved_at' => $status === 'approved' ? now()->subDays(18) : null,
            'rejected_reason' => $status === 'rejected' ? 'Kebutuhan operasional belum memungkinkan.' : null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $request;
    }

    private function seedPositionHistory(
        HrEmployee $employee,
        Carbon $effectiveDate,
        string $type,
        int $fromDepartmentId,
        int $fromPositionId,
        int $toDepartmentId,
        int $toPositionId,
        string $reason,
        ?int $userId,
    ): void {
        $history = HrPositionHistory::query()
            ->where('employee_id', $employee->id)
            ->where('type', $type)
            ->whereDate('effective_date', $effectiveDate)
            ->first() ?? new HrPositionHistory;

        $history->fill([
            'employee_id' => $employee->id,
            'from_department_id' => $fromDepartmentId,
            'from_position_id' => $fromPositionId,
            'to_department_id' => $toDepartmentId,
            'to_position_id' => $toPositionId,
            'type' => $type,
            'effective_date' => $effectiveDate->toDateString(),
            'reason' => $reason,
            'created_by' => $userId,
        ])->save();
    }
}
