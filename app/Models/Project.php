<?php

namespace App\Models;

use App\Services\MerchandisePlanningSyncService;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'archived_at' => 'datetime',
        'target_qty' => 'integer',
        'produced_qty' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updated(function (Project $project) {
            if ($project->wasChanged(['target_qty', 'design_id'])) {
                MerchandisePlanningSyncService::syncProject($project);
            }
        });
    }

    public function archivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

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

    public function lifecycleChildren(): HasMany
    {
        return $this->hasMany(Project::class, 'reference_project_id');
    }

    public function subProjects(): HasMany
    {
        return $this->hasMany(SubProject::class, 'project_id');
    }

    public function hasSubProjects(): bool
    {
        return $this->subProjects()->exists();
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
        if (! $this->target_qty || $this->target_qty <= 0) {
            return 0.0;
        }

        return round(($this->produced_qty / $this->target_qty) * 100, 1);
    }

    public function daysRemaining(): ?int
    {
        if (! $this->target_date) {
            return null;
        }

        return (int) Carbon::now()->startOfDay()->diffInDays($this->target_date->startOfDay(), false);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(HrEmployeePlacement::class, 'project_id');
    }

    public function activePlacements(): HasMany
    {
        return $this->hasMany(HrEmployeePlacement::class, 'project_id')->where('status', 'active');
    }
}
