<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\HrLeaveType;
use App\Services\LeaveRequestService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicLeaveRequestController extends Controller
{
    public function lookupPage(Request $request, string $companyCode)
    {
        $company = Company::where('code', $companyCode)->where('is_active', true)->firstOrFail();

        $employeeNumber = $request->query('employee_number');

        if ($employeeNumber) {
            $employee = LeaveRequestService::findActiveEmployeeByNumber($company->id, $employeeNumber);

            if ($employee) {
                $leaveTypes = HrLeaveType::withoutCompanyScope()
                    ->where('company_id', $company->id)
                    ->where('is_active', true)
                    ->get();

                $requests = $employee->leaveRequests()->latest('created_at')->latest('id')->get();

                return view('leave-request.show', [
                    'company' => $company,
                    'employee' => $employee,
                    'leaveTypes' => $leaveTypes,
                    'requests' => $requests,
                ]);
            }
        }

        return view('leave-request.lookup', ['company' => $company]);
    }

    public function lookup(Request $request, string $companyCode)
    {
        $company = Company::where('code', $companyCode)->where('is_active', true)->firstOrFail();

        $data = $request->validate(['employee_number' => 'required|string']);

        $employee = LeaveRequestService::findActiveEmployeeByNumber($company->id, $data['employee_number']);

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_number' => 'Nomor karyawan tidak ditemukan.',
            ]);
        }

        $leaveTypes = HrLeaveType::withoutCompanyScope()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        $requests = $employee->leaveRequests()->latest('created_at')->latest('id')->get();

        return view('leave-request.show', [
            'company' => $company,
            'employee' => $employee,
            'leaveTypes' => $leaveTypes,
            'requests' => $requests,
        ]);
    }

    public function submit(Request $request, string $companyCode)
    {
        $company = Company::where('code', $companyCode)->where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'employee_number' => 'required|string',
            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists('hr_leave_types', 'id')
                    ->where('company_id', $company->id)
                    ->where('is_active', true),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:2000',
        ]);

        $employee = LeaveRequestService::findActiveEmployeeByNumber($company->id, $data['employee_number']);

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_number' => 'Nomor karyawan tidak ditemukan.',
            ]);
        }

        LeaveRequestService::submit($employee, [
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
        ], 'public_intake');

        return redirect()
            ->route('leave-request.lookup', [
                'companyCode' => $companyCode,
                'employee_number' => $data['employee_number'],
            ])
            ->with('status', 'Pengajuan cuti berhasil dikirim.');
    }
}
