<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Merchandising extends Model
{
    protected $fillable = [
        'company_id',
        'project_id',
        'design_id',
        'version',
        'status',
        'materials_spec',
        'colors',
        'measurements',
        'special_instructions',
        'issued_date',
        'issued_by',
    ];

    protected $casts = [
        'materials_spec' => 'json',
        'colors' => 'json',
        'measurements' => 'json',
        'issued_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(RdDesign::class, 'design_id');
    }
}
