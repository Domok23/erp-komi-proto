<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubconMaterialOutItem extends Model
{
    protected $table = 'subcon_material_out_items';

    protected $fillable = [
        'subcon_material_out_id',
        'material_id',
        'qty_sent',
        'unit',
    ];

    protected $casts = [
        'qty_sent' => 'decimal:2',
    ];

    public function subconMaterialOut(): BelongsTo
    {
        return $this->belongsTo(SubconMaterialOut::class, 'subcon_material_out_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
