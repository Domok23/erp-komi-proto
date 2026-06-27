<?php

namespace App\Models;

use App\Services\InventoryService;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GoodsReceipt extends Model
{
    use BelongsToCompany;

    protected $table = 'goods_receipts';

    protected $fillable = [
        'company_id',
        'gr_number',
        'po_type',
        'po_id',
        'warehouse_id',
        'receipt_date',
        'status',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::updated(function (GoodsReceipt $goodsReceipt) {
            if ($goodsReceipt->status === 'verified' && $goodsReceipt->getOriginal('status') !== 'verified') {
                InventoryService::receiveGoods($goodsReceipt);
                self::updateShipmentOnReceipt($goodsReceipt);
                self::syncPoItemReceivedQty($goodsReceipt);
            }
        });

        static::created(function (GoodsReceipt $goodsReceipt) {
            if ($goodsReceipt->status === 'verified') {
                InventoryService::receiveGoods($goodsReceipt);
                self::updateShipmentOnReceipt($goodsReceipt);
                self::syncPoItemReceivedQty($goodsReceipt);
            }
        });
    }

    protected static function updateShipmentOnReceipt(GoodsReceipt $goodsReceipt): void
    {
        if ($goodsReceipt->po_type && $goodsReceipt->po_id) {
            // Update all non-arrived shipments for this PO to arrived
            PurchaseShipment::where('company_id', $goodsReceipt->company_id)
                ->where('po_type', $goodsReceipt->po_type)
                ->where('po_id', $goodsReceipt->po_id)
                ->where('status', '!=', 'arrived')
                ->update([
                    'status' => 'arrived',
                    'actual_arrival' => $goodsReceipt->receipt_date,
                ]);
        }
    }

    protected static function syncPoItemReceivedQty(GoodsReceipt $goodsReceipt): void
    {
        if ($goodsReceipt->po_type !== 'supplier' || ! $goodsReceipt->po_id) {
            return;
        }

        $po = PoSupplier::with('items')->find($goodsReceipt->po_id);
        if (! $po) {
            return;
        }

        $po->syncReceivedQty();
        $po->syncStatusFromItems();
    }

    public function po(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'po_type', 'po_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'goods_receipt_id');
    }

    public function shipping(): HasOne
    {
        return $this->hasOne(GoodsReceiptShipping::class, 'goods_receipt_id');
    }

    public function returs(): HasMany
    {
        return $this->hasMany(GoodsReceiptRetur::class, 'goods_receipt_id');
    }
}
