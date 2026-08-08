<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumptionRate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'design_id',
        'material_id',
        'component',
        'standard_rate',
        'unit',
        'wastage_rate',
        'notes',
    ];

    protected $casts = [
        'standard_rate' => 'decimal:4',
        'wastage_rate' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (ConsumptionRate $consumptionRate) {
            $consumptionRate->design?->recalculateEstimates();
            $consumptionRate->syncWithBoms();
        });

        static::deleted(function (ConsumptionRate $consumptionRate) {
            $consumptionRate->design?->recalculateEstimates();
            $consumptionRate->deleteFromBoms();
        });
    }

    public function syncWithBoms(): void
    {
        if (! $this->design_id) {
            return;
        }

        $boms = Bom::where('design_id', $this->design_id)->get();
        foreach ($boms as $bom) {
            $bomItem = BomItem::where('bom_id', $bom->id)
                ->where('material_id', $this->material_id)
                ->first();

            if ($bomItem) {
                if ($bomItem->is_from_rnd ?? true) {
                    $bomItem->update([
                        'component' => $this->component,
                        'quantity_per_unit' => $this->standard_rate,
                        'unit' => $this->unit,
                        'wastage_percent' => $this->wastage_rate,
                        'is_from_rnd' => true,
                    ]);
                }
            } else {
                BomItem::create([
                    'bom_id' => $bom->id,
                    'material_id' => $this->material_id,
                    'component' => $this->component,
                    'quantity_per_unit' => $this->standard_rate,
                    'unit' => $this->unit,
                    'wastage_percent' => $this->wastage_rate,
                    'notes' => $this->notes,
                    'is_from_rnd' => true,
                ]);
            }
        }
    }

    public function deleteFromBoms(): void
    {
        if (! $this->design_id) {
            return;
        }

        $bomIds = Bom::where('design_id', $this->design_id)->pluck('id');
        BomItem::whereIn('bom_id', $bomIds)
            ->where('material_id', $this->material_id)
            ->where(function ($q) {
                $q->where('is_from_rnd', true)->orWhereNull('is_from_rnd');
            })
            ->delete();
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(RdDesign::class, 'design_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
