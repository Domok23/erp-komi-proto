<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceiptRetur extends Model
{
    protected $table = 'goods_receipt_returs';

    protected $fillable = [
        'goods_receipt_id',
        'retur_number',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::updated(function (GoodsReceiptRetur $retur) {
            if ($retur->status === 'verified' && $retur->getOriginal('status') !== 'verified') {
                InventoryService::processRetur($retur);
            }
        });

        static::created(function (GoodsReceiptRetur $retur) {
            if ($retur->status === 'verified') {
                InventoryService::processRetur($retur);
            }
        });

        static::deleted(function (GoodsReceiptRetur $retur) {
            if ($retur->status === 'verified') {
                InventoryService::reverseRetur($retur);
            }
        });
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptReturItem::class, 'goods_receipt_retur_id');
    }
}
