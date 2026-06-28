<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptReturItem extends Model
{
    protected $table = 'goods_receipt_retur_items';

    protected $fillable = [
        'goods_receipt_retur_id',
        'material_id',
        'qty_returned',
        'reason',
    ];

    protected $casts = [
        'qty_returned' => 'decimal:2',
    ];

    public function retur(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptRetur::class, 'goods_receipt_retur_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
