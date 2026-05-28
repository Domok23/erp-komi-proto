<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptShipping extends Model
{
    protected $table = 'goods_receipt_shipping';

    protected $fillable = [
        'goods_receipt_id',
        'carrier',
        'tracking_number',
        'shipping_cost',
        'received_condition',
        'notes',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }
}
