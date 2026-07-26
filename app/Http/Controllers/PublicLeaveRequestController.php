<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use App\Services\LeaveRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

                $balances = LeaveRequestService::getLeaveBalances($employee, now()->year);

                return view('leave-request.show', [
                    'company' => $company,
                    'employee' => $employee,
                    'leaveTypes' => $leaveTypes,
                    'requests' => $requests,
                    'balances' => $balances,
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

        $selectedType = HrLeaveType::find($request->input('leave_type_id'));
        $isSick = $selectedType && ($selectedType->is_sick_type || str_contains(strtolower($selectedType->name), 'sakit'));

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
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
            'attachment' => [
                $isSick ? 'required' : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ], [
            'end_date.after_or_equal' => 'Tanggal selesai cuti tidak boleh lebih awal dari tanggal mulai cuti.',
            'attachment.required' => 'Surat Dokter wajib diunggah untuk jenis cuti sakit.',
        ]);

        $employeeNumber = $request->input('employee_number');

        if ($validator->fails()) {
            return redirect()
                ->route('leave-request.lookup', array_filter([
                    'companyCode' => $companyCode,
                    'employee_number' => $employeeNumber,
                ]))
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $employee = LeaveRequestService::findActiveEmployeeByNumber($company->id, $data['employee_number']);

        if (! $employee) {
            return redirect()
                ->route('leave-request.lookup', $companyCode)
                ->withErrors(['employee_number' => 'Nomor karyawan tidak ditemukan.'])
                ->withInput();
        }

        $filePath = null;
        if ($request->hasFile('attachment')) {
            $filePath = $request->file('attachment')->store('hr/leaves', 'public');
        }

        try {
            LeaveRequestService::submit($employee, [
                'leave_type_id' => $data['leave_type_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'] ?? null,
                'file_path' => $filePath,
            ], 'public_intake');
        } catch (\App\Exceptions\HrLeaveRequestException $e) {
            $errorField = str_contains(strtolower($e->getMessage()), 'kuota') ? 'leave_type_id' : 'start_date';

            return redirect()
                ->route('leave-request.lookup', [
                    'companyCode' => $companyCode,
                    'employee_number' => $employee->employee_number,
                ])
                ->withErrors([$errorField => $e->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('leave-request.lookup', [
                'companyCode' => $companyCode,
                'employee_number' => $employee->employee_number,
            ])
            ->with('status', 'Pengajuan cuti berhasil dikirim.');
    }

    public function attachment(HrLeaveRequest $leaveRequest)
    {
        if (! $leaveRequest->file_path || ! Storage::disk('public')->exists($leaveRequest->file_path)) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        return Storage::disk('public')->response($leaveRequest->file_path);
    }
}
