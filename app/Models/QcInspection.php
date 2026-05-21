<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcInspection extends Model
{
    protected $fillable = [
        'company_id',
        'production_order_id',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }
}