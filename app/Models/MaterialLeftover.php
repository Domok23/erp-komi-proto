<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialLeftover extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'job_order_id',
        'material_id',
        'leftover_date',
        'qty',
        'unit',
        'condition',
        'status',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'leftover_date' => 'date',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
