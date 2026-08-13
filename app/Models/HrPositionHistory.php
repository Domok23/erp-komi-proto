<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrPositionHistory extends Model
{
    protected $table = 'hr_position_histories';

    protected $fillable = [
        'employee_id',
        'from_department_id',
        'from_position_id',
        'to_department_id',
        'to_position_id',
        'type',
        'effective_date',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'from_department_id');
    }

    public function fromPosition(): BelongsTo
    {
        return $this->belongsTo(HrPosition::class, 'from_position_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'to_department_id');
    }

    public function toPosition(): BelongsTo
    {
        return $this->belongsTo(HrPosition::class, 'to_position_id');
    }
}
