<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HrEmployee extends Model
{
    use BelongsToCompany;

    protected $table = 'hr_employees';

    protected $fillable = [
        'company_id',
        'candidate_id',
        'employee_number',
        'name',
        'nik',
        'phone',
        'email',
        'address',
        'department_id',
        'position_id',
        'join_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(HrCandidate::class, 'candidate_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'department_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(HrPosition::class, 'position_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(HrEmploymentContract::class, 'employee_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(HrEmployeePlacement::class, 'employee_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(HrAttendance::class, 'employee_id');
    }

    public function overtimeRecords(): HasMany
    {
        return $this->hasMany(HrOvertimeRecord::class, 'employee_id');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(HrLeaveBalance::class, 'employee_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(HrLeaveRequest::class, 'employee_id');
    }

    public function warningLetters(): HasMany
    {
        return $this->hasMany(HrWarningLetter::class, 'employee_id');
    }

    public function positionHistories(): HasMany
    {
        return $this->hasMany(HrPositionHistory::class, 'employee_id');
    }

    public function activePlacement(): HasOne
    {
        return $this->hasOne(HrEmployeePlacement::class, 'employee_id')
            ->where('status', 'active');
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(HrEmploymentContract::class, 'employee_id')
            ->where('status', 'active');
    }
}
