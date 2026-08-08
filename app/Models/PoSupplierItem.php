<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoSupplierItem extends Model
{
    protected $table = 'po_supplier_items';

    protected $fillable = [
        'po_supplier_id',
        'project_id',
        'sub_project_id',
        'material_id',
        'description',
        'qty',
        'unit',
        'unit_price',
        'total_price',
        'qty_received',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'qty_received' => 'decimal:2',
    ];

    public function poSupplier(): BelongsTo
    {
        return $this->belongsTo(PoSupplier::class, 'po_supplier_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function subProject(): BelongsTo
    {
        return $this->belongsTo(SubProject::class, 'sub_project_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
