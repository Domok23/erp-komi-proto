<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'project_code',
        'name',
        'description',
        'type',
        'status',
        'customer_id',
        'sales_order_id',
        'design_id',
        'bom_id',
        'reference_project_id',
        'approved_at',
        'approved_by',
        'start_date',
        'target_date',
        'completed_at',
        'target_qty',
        'produced_qty',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'target_qty' => 'integer',
        'produced_qty' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(RdDesign::class, 'design_id');
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function referenceProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'reference_project_id');
    }

    public function subProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'reference_project_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function merchandisePlannings(): HasMany
    {
        return $this->hasMany(MerchandisePlanning::class, 'project_id');
    }

    public function costings(): HasMany
    {
        return $this->hasMany(Costing::class, 'project_id');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'project_id');
    }

    public function progressPercent(): float
    {
        if (!$this->target_qty || $this->target_qty <= 0) {
            return 0.0;
        }

        return round(($this->produced_qty / $this->target_qty) * 100, 1);
    }

    public function daysRemaining(): ?int
    {
        if (!$this->target_date) {
            return null;
        }

        return (int) \Illuminate\Support\Carbon::now()->startOfDay()->diffInDays($this->target_date->startOfDay(), false);
    }
}

