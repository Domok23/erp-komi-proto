<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'so_number',
        'project_id',
        'costing_id',
        'customer_id',
        'order_date',
        'delivery_date',
        'quantity',
        'unit_price',
        'status',
        'currency',
        'exchange_rate',
        'subtotal',
        'ppn_percent',
        'ppn_amount',
        'shipping_cost',
        'grand_total',
        'down_payment_pct',
        'down_payment_amount',
        'payment_terms',
        'notes',
        'customer_signature',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'ppn_percent' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'down_payment_pct' => 'decimal:2',
        'down_payment_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function costing(): BelongsTo
    {
        return $this->belongsTo(Costing::class, 'costing_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function invoiceSales(): HasMany
    {
        return $this->hasMany(InvoiceSales::class, 'sales_order_id');
    }
}
