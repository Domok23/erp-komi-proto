<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use BelongsToCompany;

    protected $table = 'inventory_stocks';

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'material_id',
        'quantity',
        'reserved_qty',
        'available_qty',
        'unit',
        'min_stock',
        'location',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'reserved_qty' => 'decimal:3',
        'available_qty' => 'decimal:3',
        'min_stock' => 'decimal:3',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
