<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'contact_person',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'npwp',
        'payment_terms',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invoiceSales(): HasManyThrough
    {
        return $this->hasManyThrough(InvoiceSales::class, SalesOrder::class);
    }

    /**
     * @return list<string>
     */
    public function getDeletionBlockers(): array
    {
        $blockers = [];

        if ($count = $this->salesOrders()->count()) {
            $blockers[] = "{$count} Sales Order(s)";
        }
        if ($count = $this->projects()->count()) {
            $blockers[] = "{$count} Project(s)";
        }
        if ($count = $this->invoiceSales()->count()) {
            $blockers[] = "{$count} Sales Invoice(s)";
        }

        return $blockers;
    }
}
