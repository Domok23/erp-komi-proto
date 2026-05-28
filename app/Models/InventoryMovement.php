<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'inventory_id',
        'material_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'before_qty',
        'after_qty',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'before_qty' => 'decimal:2',
        'after_qty' => 'decimal:2',
    ];

    
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
