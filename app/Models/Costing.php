<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Costing extends Model
{
    use BelongsToCompany;

    private const STATUS_TRANSITIONS = [
        'draft' => ['calculated'],
        'calculated' => ['draft', 'submitted'],
        'submitted' => ['draft', 'approved', 'rejected'],
        'approved' => [],
        'rejected' => [],
    ];

    protected $fillable = [
        'company_id',
        'project_id',
        'design_id',
        'costing_date',
        'version',
        'status',
        'material_cost',
        'mp_cost',
        'overhead_pct',
        'overhead_amount',
        'shipping_cost',
        'profit_margin_pct',
        'profit_margin_amount',
        'landed_cost',
        'selling_price',
        'currency',
        'notes',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'costing_date' => 'date',
        'material_cost' => 'decimal:2',
        'mp_cost' => 'integer',
        'overhead_pct' => 'decimal:2',
        'overhead_amount' => 'decimal:2',
        'shipping_cost' => 'integer',
        'profit_margin_pct' => 'decimal:2',
        'profit_margin_amount' => 'decimal:2',
        'landed_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'rejected_by' => 'integer',
        'rejected_at' => 'datetime',
        'submitted_by' => 'integer',
        'submitted_at' => 'datetime',
    ];

    // --- Relationships ---

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(RdDesign::class, 'design_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    // --- Status helpers ---

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    public function transitionTo(string $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from '{$this->status}' to '{$newStatus}'.",
            ]);
        }

        $this->update(['status' => $newStatus]);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'calculated', 'submitted'], true);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['approved', 'rejected'], true);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'calculated';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBeUnsubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBeDuplicated(): bool
    {
        return true; // bisa duplicate dari status mana saja
    }

    public function canCreateNewVersion(): bool
    {
        return $this->status === 'rejected';
    }

    public function hasAllCostsFilled(): bool
    {
        return $this->material_cost > 0
            && $this->mp_cost > 0
            && $this->shipping_cost > 0
            && $this->selling_price > 0;
    }
}
