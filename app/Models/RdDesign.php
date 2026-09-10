<?php

namespace App\Models;

use App\Services\CostingCalculatorService;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RdDesign extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'version',
        'parent_design_id',
        'description',
        'product_type',
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

    public function getFormattedSelectLabelAttribute(): string
    {
        return $this->version ? "{$this->name} (v{$this->version})" : $this->name;
    }

    public function parentDesign(): BelongsTo
    {
        return $this->belongsTo(RdDesign::class, 'parent_design_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(RdDesign::class, 'parent_design_id');
    }

    public function createRevision(string $newVersion): self
    {
        $clone = $this->replicate([
            'created_at',
            'updated_at',
        ]);
        $clone->version = $newVersion;
        $clone->parent_design_id = $this->id;
        $clone->status = 'draft';
        $clone->code = $this->code.'-v'.str_replace('.', '_', $newVersion);
        $clone->save();

        foreach ($this->consumptionRates as $rate) {
            $clonedRate = $rate->replicate(['created_at', 'updated_at']);
            $clonedRate->design_id = $clone->id;
            $clonedRate->save();
        }

        $clone->recalculateEstimates();

        return $clone;
    }

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

    /**
     * @return list<string>
     */
    public function getDeletionBlockers(): array
    {
        $blockers = [];

        if ($count = $this->projects()->count()) {
            $blockers[] = "{$count} Project(s)";
        }
        if ($count = $this->revisions()->count()) {
            $blockers[] = "{$count} Revision(s)";
        }
        if ($count = $this->merchandisePlannings()->count()) {
            $blockers[] = "{$count} Merchandise Planning(s)";
        }
        if ($count = $this->costings()->count()) {
            $blockers[] = "{$count} Costing(s)";
        }
        if ($count = $this->boms()->count()) {
            $blockers[] = "{$count} BOM(s)";
        }

        return $blockers;
    }

    public function recalculateEstimates(): void
    {
        $materialCost = 0;
        $wastage = config('costing.wastage_pct', 3);
        $importCostPct = config('costing.import_cost_pct', 5);

        foreach ($this->consumptionRates()->with('material')->get() as $rate) {
            $material = $rate->material;
            if ($material) {
                $wastageMultiplier = 1 + ($wastage / 100);
                $qtyAdjusted = $rate->standard_rate * $wastageMultiplier;

                $effectivePrice = $material->price;
                if ($material->is_import) {
                    $effectivePrice *= (1 + ($importCostPct / 100));
                }

                $materialCost += $qtyAdjusted * $effectivePrice;
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
