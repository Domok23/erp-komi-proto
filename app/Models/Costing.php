<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Costing extends Model
{
    protected $fillable = [
        'company_id',
        'project_id',
        'design_id',
        'version',
        'status',
        'material_cost',
        'mp_cost',
        'overhead_pct',
        'overhead_amount',
        'shipping_cost',
        'profit_margin_pct',
        'profit_margin_amount',
        'landed_cost',
        'selling_price',
        'currency',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'material_cost' => 'decimal:2',
        'mp_cost' => 'decimal:2',
        'overhead_pct' => 'decimal:2',
        'overhead_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'profit_margin_pct' => 'decimal:2',
        'profit_margin_amount' => 'decimal:2',
        'landed_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(RdDesign::class, 'design_id');
    }
}
