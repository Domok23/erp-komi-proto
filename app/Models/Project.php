<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'type',
        'status',
        'customer_id',
        'sales_order_id',
        'design_id',
        'start_date',
        'target_date',
        'completed_at',
        'target_qty',
        'produced_qty',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'target_qty' => 'decimal:2',
        'produced_qty' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(RdDesign::class, 'design_id');
    }

    public function merchandisings(): HasMany
    {
        return $this->hasMany(Merchandising::class);
    }

    public function costings(): HasMany
    {
        return $this->hasMany(Costing::class);
    }

    public function projectBoms(): HasMany
    {
        return $this->hasMany(ProjectBom::class);
    }

    public function projectConsumptions(): HasMany
    {
        return $this->hasMany(ProjectConsumption::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }
}
