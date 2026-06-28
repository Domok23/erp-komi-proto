<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOrderMaterial extends Model
{
    use BelongsToCompany;

    protected $table = 'job_order_materials';

    protected $fillable = [
        'company_id',
        'job_order_id',
        'merchandising_planning_item_id',
        'material_id',
        'planned_qty',
        'usage_qty',
        'leftover_qty',
        'unit',
        'is_selected',
        'notes',
    ];

    protected $casts = [
        'planned_qty' => 'decimal:3',
        'usage_qty' => 'decimal:3',
        'leftover_qty' => 'decimal:3',
        'is_selected' => 'boolean',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function merchandisingPlanningItem(): BelongsTo
    {
        return $this->belongsTo(MerchandisePlanningItem::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
