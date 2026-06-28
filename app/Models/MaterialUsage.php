<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialUsage extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'job_order_id',
        'material_id',
        'usage_date',
        'planned_qty',
        'actual_qty',
        'waste_qty',
        'unit',
        'unit_price',
        'total_cost',
        'status',
        'notes',
    ];

    protected $casts = [
        'planned_qty' => 'decimal:3',
        'actual_qty' => 'decimal:3',
        'waste_qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'usage_date' => 'date',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
