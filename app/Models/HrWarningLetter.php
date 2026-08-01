<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrWarningLetter extends Model
{
    use BelongsToCompany;

    protected $table = 'hr_warning_letters';

    protected $fillable = [
        'company_id',
        'employee_id',
        'level',
        'letter_number',
        'issued_date',
        'reason',
        'file_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'issued_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
