<?php

namespace App\Models;

use App\Services\InventoryService;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'production_number',
        'project_id',
        'sub_project_id',
        'merchandising_planning_id',
        'planned_qty',
        'completed_qty',
        'status',
        'notes',
        'start_date',
        'end_date',
    ];

    protected static function booted(): void
    {
        static::updated(function (ProductionOrder $po) {
            if ($po->status === 'completed' && $po->getOriginal('status') !== 'completed') {
                InventoryService::processProductionOrderCompletion($po);
            } elseif ($po->status !== 'completed' && $po->getOriginal('status') === 'completed') {
                InventoryService::reverseProductionOrderCompletion($po);
            }
        });

        static::created(function (ProductionOrder $po) {
            if ($po->status === 'completed') {
                InventoryService::processProductionOrderCompletion($po);
            }
        });

        static::deleted(function (ProductionOrder $po) {
            if ($po->status === 'completed') {
                InventoryService::reverseProductionOrderCompletion($po);
            }
        });
    }

    protected $casts = [
        'planned_qty' => 'decimal:2',
        'completed_qty' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function subProject(): BelongsTo
    {
        return $this->belongsTo(SubProject::class);
    }

    public function merchandisingPlanning(): BelongsTo
    {
        return $this->belongsTo(MerchandisePlanning::class);
    }

    public function jobOrders(): HasMany
    {
        return $this->hasMany(JobOrder::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterial::class);
    }

    public function projectPlacements(): HasMany
    {
        return $this->hasMany(HrEmployeePlacement::class, 'project_id', 'project_id');
    }

    public function getDeletionBlockers(): array
    {
        $blockers = [];

        if (! in_array($this->status, ['planned', 'cancelled'])) {
            $blockers[] = "status is '{$this->status}' (only Planned or Cancelled production orders can be deleted)";
        }

        $jobOrdersCount = $this->jobOrders()->count();
        if ($jobOrdersCount > 0) {
            $blockers[] = "{$jobOrdersCount} linked Job Order(s)";
        }

        return $blockers;
    }
}
