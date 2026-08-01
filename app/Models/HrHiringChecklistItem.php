<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrHiringChecklistItem extends Model
{
    protected $table = 'hr_hiring_checklist_items';

    protected $fillable = [
        'candidate_id',
        'type',
        'label',
        'is_required',
        'status',
        'file_path',
        'notes',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(HrCandidate::class, 'candidate_id');
    }

    public function isSatisfied(): bool
    {
        return in_array($this->status, ['done', 'waived'], true);
    }
}
