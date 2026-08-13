<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOrder extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'production_order_id',
        'merchandising_planning_id',
        'job_order_number',
        'task_type',
        'planned_qty',
        'completed_qty',
        'status',
        'start_date',
        'end_date',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'planned_qty' => 'integer',
        'completed_qty' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'assigned_to' => 'array',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function merchandisingPlanning(): BelongsTo
    {
        return $this->belongsTo(MerchandisePlanning::class);
    }

    public function qcInspections(): HasMany
    {
        return $this->hasMany(QcInspection::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(JobOrderMaterial::class);
    }
}
