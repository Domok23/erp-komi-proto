<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrLeaveType extends Model
{
    use BelongsToCompany;

    protected $table = 'hr_leave_types';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'default_quota_days',
        'requires_document',
        'is_sick_type',
        'is_active',
    ];

    protected $casts = [
        'requires_document' => 'boolean',
        'is_sick_type' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(HrLeaveBalance::class, 'leave_type_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(HrLeaveRequest::class, 'leave_type_id');
    }

    public function isQuotaBased(): bool
    {
        return $this->default_quota_days !== null;
    }
}
