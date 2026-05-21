<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RdDesign extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'category',
        'status',
        'sample_photo',
        'tech_drawing',
        'notes',
        'estimated_material_cost',
        'estimated_mp_cost',
        'estimated_overhead_pct',
        'estimated_profit_margin_pct',
        'estimated_selling_price',
    ];

    protected $casts = [
        'estimated_material_cost' => 'decimal:2',
        'estimated_mp_cost' => 'decimal:2',
        'estimated_overhead_pct' => 'decimal:2',
        'estimated_profit_margin_pct' => 'decimal:2',
        'estimated_selling_price' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'design_id');
    }

    public function merchandisings(): HasMany
    {
        return $this->hasMany(Merchandising::class, 'design_id');
    }

    public function costings(): HasMany
    {
        return $this->hasMany(Costing::class, 'design_id');
    }
}
