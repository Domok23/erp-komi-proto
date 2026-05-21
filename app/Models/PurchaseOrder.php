<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'company_id',
        'po_number',
        'type',
        'supplier_id',
        'subcon_id',
        'order_date',
        'delivery_date',
        'status',
        'currency',
        'subtotal',
        'tax_amount',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function subcon(): BelongsTo
    {
        return $this->belongsTo(Subcon::class);
    }

    public function purchaseReceipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
