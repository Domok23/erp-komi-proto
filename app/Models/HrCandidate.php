<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HrCandidate extends Model
{
    use BelongsToCompany;

    protected $table = 'hr_candidates';

    protected $fillable = [
        'company_id',
        'name',
        'nik',
        'phone',
        'email',
        'address',
        'source',
        'status',
        'notes',
        'hired_at',
        'hire_override_reason',
        'hire_override_by',
        'hire_override_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'hired_at' => 'datetime',
        'hire_override_at' => 'datetime',
    ];

    public function checklistItems(): HasMany
    {
        return $this->hasMany(HrHiringChecklistItem::class, 'candidate_id');
    }

    public function employee(): HasOne
    {
        return $this->hasOne(HrEmployee::class, 'candidate_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function seedDefaultChecklist(): void
    {
        if ($this->checklistItems()->exists()) {
            return;
        }

        $this->checklistItems()->createMany([
            [
                'type' => 'mcu',
                'label' => 'Medical Check Up',
                'is_required' => true,
                'status' => 'pending',
            ],
            [
                'type' => 'bank_account',
                'label' => 'Bank Account',
                'is_required' => true,
                'status' => 'pending',
            ],
        ]);
    }
}
