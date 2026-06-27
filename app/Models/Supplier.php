<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'contact_person',
        'address',
        'city',
        'phone',
        'email',
        'npwp',
        'bank_account',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function poSuppliers(): HasMany
    {
        return $this->hasMany(PoSupplier::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'po_id')->where('po_type', 'supplier');
    }

    public function invoicePurchases(): HasMany
    {
        return $this->hasMany(InvoicePurchase::class, 'reference_id')->where('purchase_type', 'po_supplier');
    }
}
