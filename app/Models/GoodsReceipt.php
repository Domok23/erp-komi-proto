<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'company_id',
        'gr_number',
        'purchase_receipt_id',
        'supplier_id',
        'receipt_date',
        'invoice_number',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
