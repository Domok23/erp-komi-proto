<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectConsumption extends Model
{
    protected $fillable = [
        'project_id',
        'material_id',
        'consumption_date',
        'quantity',
        'source',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'consumption_date' => 'date',
        'quantity' => 'decimal:4',
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
