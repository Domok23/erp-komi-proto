<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceSales extends Model
{
    use BelongsToCompany;

    protected $table = 'invoice_sales';

    protected $fillable = [
        'company_id',
        'sales_order_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'ppn_percent',
        'ppn_amount',
        'shipping_cost',
        'grand_total',
        'paid_amount',
        'status',
        'is_tax_invoice',
        'tax_invoice_number',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'ppn_percent' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'is_tax_invoice' => 'boolean',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id')->where('invoice_type', 'sales');
    }
}
