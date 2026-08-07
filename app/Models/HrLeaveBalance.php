<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrLeaveBalance extends Model
{
    protected $table = 'hr_leave_balances';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'quota_days',
        'used_days',
        'created_by',
        'updated_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }

    public function remainingDays(): int
    {
        return $this->quota_days - $this->used_days;
    }
}
