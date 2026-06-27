<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $table = 'stock_transfers';

    protected $fillable = [
        'from_company_id',
        'to_company_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_number',
        'transfer_date',
        'status',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::updated(function (StockTransfer $transfer) {
            if ($transfer->status === 'shipped' && $transfer->getOriginal('status') !== 'shipped') {
                InventoryService::executeTransferShipment($transfer);
            }
            if ($transfer->status === 'received' && $transfer->getOriginal('status') !== 'received') {
                InventoryService::executeTransferReceipt($transfer);
            }
        });

        static::created(function (StockTransfer $transfer) {
            if ($transfer->status === 'shipped') {
                InventoryService::executeTransferShipment($transfer);
            }
            if ($transfer->status === 'received') {
                InventoryService::executeTransferReceipt($transfer);
            }
        });
    }

    public function fromCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'from_company_id');
    }

    public function toCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'to_company_id');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
