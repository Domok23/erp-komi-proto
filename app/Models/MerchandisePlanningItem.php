<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchandisePlanningItem extends Model
{
    protected $fillable = [
        'merchandise_planning_id',
        'material_id',
        'component',
        'supplier_id',
        'subcon_id',
        'planned_qty',
        'unit',
        'unit_price',
        'total_price',
        'is_subcon',
        'notes',
        'is_from_rnd',
    ];

    protected $casts = [
        'planned_qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_subcon' => 'boolean',
        'is_from_rnd' => 'boolean',
    ];

    public function planning(): BelongsTo
    {
        return $this->belongsTo(MerchandisePlanning::class, 'merchandise_planning_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function subcon(): BelongsTo
    {
        return $this->belongsTo(Subcon::class, 'subcon_id');
    }
}
