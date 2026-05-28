<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Merchandising extends Model
{
    use BelongsToCompany;

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

    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(RdDesign::class, 'design_id');
    }
}
