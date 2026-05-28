<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'production_number',
        'project_id',
        'planned_qty',
        'completed_qty',
        'status',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'planned_qty' => 'decimal:2',
        'completed_qty' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function qcInspections(): HasMany
    {
        return $this->hasMany(QcInspection::class);
    }
}