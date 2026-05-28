<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubconMaterialInItem extends Model
{
    protected $table = 'subcon_material_in_items';

    protected $fillable = [
        'subcon_material_in_id',
        'material_id',
        'qty_received',
        'qty_rejected',
        'unit',
    ];

    protected $casts = [
        'qty_received' => 'decimal:2',
        'qty_rejected' => 'decimal:2',
    ];

    public function subconMaterialIn(): BelongsTo
    {
        return $this->belongsTo(SubconMaterialIn::class, 'subcon_material_in_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
