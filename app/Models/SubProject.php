<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubProject extends Model
{
    use BelongsToCompany;

    protected static function booted(): void
    {
        static::deleting(function (SubProject $subProject) {
            $blockers = $subProject->getDeletionBlockers();
            if (! empty($blockers)) {
                throw new \Exception("Cannot delete SubProject '{$subProject->name}' because active transactions are attached to it: ".implode(', ', $blockers));
            }
        });
    }

    protected $fillable = [
        'company_id',
        'project_id',
        'name',
        'code',
        'category',
        'bom_id',
        'target_qty',
        'produced_qty',
    ];

    protected $casts = [
        'target_qty' => 'integer',
        'produced_qty' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function getStatusAttribute(): ?string
    {
        return $this->project?->status;
    }

    public function poSupplierItems(): HasMany
    {
        return $this->hasMany(PoSupplierItem::class, 'sub_project_id');
    }

    public function poSubconItems(): HasMany
    {
        return $this->hasMany(PoSubconItem::class, 'sub_project_id');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'sub_project_id');
    }

    public function costings(): HasMany
    {
        return $this->hasMany(Costing::class, 'sub_project_id');
    }

    public function materialReservations(): HasMany
    {
        return $this->hasMany(MaterialReservation::class, 'sub_project_id');
    }

    public function merchandisePlannings(): HasMany
    {
        return $this->hasMany(MerchandisePlanning::class, 'sub_project_id');
    }

    /**
     * @return list<string>
     */
    public function getDeletionBlockers(): array
    {
        $blockers = [];

        if ($count = $this->merchandisePlannings()->count()) {
            $blockers[] = "{$count} Merchandise Planning(s)";
        }
        if ($count = $this->costings()->count()) {
            $blockers[] = "{$count} Costing(s)";
        }
        if ($count = $this->productionOrders()->count()) {
            $blockers[] = "{$count} Production Order(s)";
        }
        if ($count = $this->materialReservations()->count()) {
            $blockers[] = "{$count} Material Reservation(s)";
        }
        if ($count = $this->poSupplierItems()->count()) {
            $blockers[] = "{$count} PO Supplier Item(s)";
        }
        if ($count = $this->poSubconItems()->count()) {
            $blockers[] = "{$count} PO Subcon Item(s)";
        }

        return $blockers;
    }

    public function effectiveBom(): ?Bom
    {
        return $this->bom ?? $this->project?->bom;
    }

    public function effectiveDesign(): ?RdDesign
    {
        return $this->project?->design;
    }
}
