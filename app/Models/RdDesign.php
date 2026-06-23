<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RdDesign extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'bag_type',
        'status',
        'sample_photo',
        'tech_drawing',
        'reference_image',
        'tech_pack',
        'brand',
        'size_range',
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

    public function boms(): HasMany
    {
        return $this->hasMany(Bom::class, 'design_id');
    }

    public function consumptionRates(): HasMany
    {
        return $this->hasMany(ConsumptionRate::class, 'design_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'design_id');
    }

    public function merchandisePlannings(): HasMany
    {
        return $this->hasMany(MerchandisePlanning::class, 'design_id');
    }

    public function costings(): HasMany
    {
        return $this->hasMany(Costing::class, 'design_id');
    }
}
