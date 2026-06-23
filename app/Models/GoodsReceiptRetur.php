<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptRetur extends Model
{
    protected $table = 'goods_receipt_returs';

    protected $fillable = [
        'goods_receipt_id',
        'retur_number',
        'material_id',
        'qty_returned',
        'reason',
        'status',
    ];

    protected $casts = [
        'qty_returned' => 'decimal:2',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
