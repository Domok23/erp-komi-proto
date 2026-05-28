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
        'customer_id',
        'order_date',
        'delivery_date',
        'status',
        'currency',
        'exchange_rate',
        'subtotal',
        'tax_pct',
        'tax_amount',
        'total_amount',
        'down_payment_pct',
        'down_payment_amount',
        'payment_terms',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'tax_pct' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'down_payment_pct' => 'decimal:2',
        'down_payment_amount' => 'decimal:2',
    ];

    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
