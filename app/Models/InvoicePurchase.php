<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoicePurchase extends Model
{
    use BelongsToCompany;

    protected $table = 'invoice_purchases';

    protected $fillable = [
        'company_id',
        'invoice_number',
        'purchase_type',
        'reference_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'purchase_type', 'reference_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id')->where('invoice_type', 'purchase');
    }
}
