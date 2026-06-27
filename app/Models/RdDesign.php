<?php

namespace App\Models;

use App\Services\CostingCalculatorService;
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

    public function recalculateEstimates(): void
    {
        $materialCost = 0;
        foreach ($this->consumptionRates()->with('material')->get() as $rate) {
            $material = $rate->material;
            if ($material) {
                $wastageMultiplier = 1 + ($rate->wastage_rate / 100);
                $qtyAdjusted = $rate->standard_rate * $wastageMultiplier;
                $materialCost += $qtyAdjusted * $material->price;
            }
        }

        $mpCost = CostingCalculatorService::getMpRatePerUnit();
        $overheadPct = CostingCalculatorService::getDefaultOverheadPct();
        $profitPct = CostingCalculatorService::getDefaultProfitMarginPct();

        $subtotal = $materialCost + $mpCost;
        $overheadAmount = $subtotal * ($overheadPct / 100);
        $landedCost = $subtotal + $overheadAmount; // shipping is 0 at design stage
        $profitAmount = $landedCost * ($profitPct / 100);
        $sellingPrice = $landedCost + $profitAmount;

        $this->update([
            'estimated_material_cost' => $materialCost,
            'estimated_mp_cost' => $mpCost,
            'estimated_overhead_pct' => $overheadPct,
            'estimated_profit_margin_pct' => $profitPct,
            'estimated_selling_price' => $sellingPrice,
        ]);
    }
}
