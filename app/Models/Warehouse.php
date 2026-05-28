<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use BelongsToCompany;

    protected $table = 'warehouses';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class, 'warehouse_id');
    }
}
