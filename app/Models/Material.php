<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'category',
        'unit',
        'stock',
        'min_stock',
        'price',
        'supplier_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function projectBoms(): HasMany
    {
        return $this->hasMany(ProjectBom::class);
    }

    public function projectConsumptions(): HasMany
    {
        return $this->hasMany(ProjectConsumption::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
