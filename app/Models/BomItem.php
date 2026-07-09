<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomItem extends Model
{
    protected $fillable = [
        'bom_id',
        'material_id',
        'category',
        'quantity_per_unit',
        'unit',
        'wastage_percent',
        'notes',
        'is_from_rnd',
    ];

    protected $casts = [
        'quantity_per_unit' => 'decimal:4',
        'wastage_percent' => 'decimal:2',
        'is_from_rnd' => 'boolean',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
