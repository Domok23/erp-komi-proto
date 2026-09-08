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

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'warehouse_id');
    }

    public function materialReservations(): HasMany
    {
        return $this->hasMany(MaterialReservation::class, 'warehouse_id');
    }

    public function stockTransfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function stockTransfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    /**
     * @return list<string>
     */
    public function getDeletionBlockers(): array
    {
        $blockers = [];

        $stocksWithQty = $this->inventoryStocks()->where('quantity', '>', 0)->count();
        if ($stocksWithQty > 0) {
            $blockers[] = "{$stocksWithQty} item(s) currently in stock";
        } elseif ($totalStockRows = $this->inventoryStocks()->count()) {
            $blockers[] = "{$totalStockRows} inventory stock record(s)";
        }

        if ($count = $this->goodsReceipts()->count()) {
            $blockers[] = "{$count} Goods Receipt(s)";
        }
        if ($count = $this->materialReservations()->count()) {
            $blockers[] = "{$count} Material Reservation(s)";
        }
        $transfersCount = $this->stockTransfersFrom()->count() + $this->stockTransfersTo()->count();
        if ($transfersCount > 0) {
            $blockers[] = "{$transfersCount} Stock Transfer(s)";
        }

        return $blockers;
    }
}
