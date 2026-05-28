<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoSupplier extends Model
{
    use BelongsToCompany;

    protected $table = 'po_suppliers';

    protected $fillable = [
        'company_id',
        'po_number',
        'project_id',
        'supplier_id',
        'po_date',
        'delivery_date',
        'status',
        'subtotal',
        'ppn_percent',
        'ppn_amount',
        'grand_total',
        'notes',
    ];

    protected $casts = [
        'po_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'ppn_percent' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PoSupplierItem::class, 'po_supplier_id');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'po_id')->where('po_type', 'supplier');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoicePurchase::class, 'reference_id')->where('purchase_type', 'po_supplier');
    }
}
