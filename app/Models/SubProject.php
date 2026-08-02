<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubProject extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'project_id',
        'name',
        'code',
        'category',
        'bom_id',
        'target_qty',
        'produced_qty',
        'review_status',
        'review_notes',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'target_qty' => 'integer',
        'produced_qty' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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
