<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'warehouse_type',
        'material_id',
        'quantity',
        'reserved_qty',
        'available_qty',
        'location',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'reserved_qty' => 'decimal:2',
        'available_qty' => 'decimal:2',
    ];

    
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
