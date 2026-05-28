<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceipt extends Model
{
    use BelongsToCompany;

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

    
    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
