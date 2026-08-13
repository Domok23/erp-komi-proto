<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcInspection extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'job_order_id',
        'inspection_number',
        'inspection_date',
        'sample_size',
        'passed_qty',
        'failed_qty',
        'result',
        'notes',
        'inspector',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'sample_size' => 'integer',
        'passed_qty' => 'integer',
        'failed_qty' => 'integer',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }
}
