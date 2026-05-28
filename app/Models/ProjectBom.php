<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBom extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'project_id',
        'material_id',
        'quantity',
        'wastage_pct',
        'final_quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'wastage_pct' => 'decimal:2',
        'final_quantity' => 'decimal:4',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
