<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchandisePlanning extends Model
{
    use BelongsToCompany;

    protected $table = 'merchandise_plannings';

    protected $fillable = [
        'company_id',
        'project_id',
        'sub_project_id',
        'design_id',
        'planning_date',
        'status',
        'total_material_cost',
        'total_subcon_cost',
        'special_instructions',
    ];

    protected $casts = [
        'planning_date' => 'date',
        'total_material_cost' => 'decimal:2',
        'total_subcon_cost' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function subProject(): BelongsTo
    {
        return $this->belongsTo(SubProject::class, 'sub_project_id');
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(RdDesign::class, 'design_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MerchandisePlanningItem::class, 'merchandise_planning_id');
    }
}
