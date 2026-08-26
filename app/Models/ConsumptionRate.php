<?php

namespace App\Models;

use App\Services\MerchandisePlanningSyncService;
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
            MerchandisePlanningSyncService::syncConsumptionRate($consumptionRate);
        });

        static::deleted(function (ConsumptionRate $consumptionRate) {
            $consumptionRate->design?->recalculateEstimates();
            MerchandisePlanningSyncService::syncConsumptionRate($consumptionRate, true);
        });
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
