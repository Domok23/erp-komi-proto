<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'category',
        'unit',
        'weight_kg',
        'dimensions',
        'is_active',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
