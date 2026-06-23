<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PurchaseTracking extends Model
{
    use BelongsToCompany;

    protected $table = 'purchase_trackings';

    protected $fillable = [
        'company_id',
        'po_type',
        'po_id',
        'tracking_status',
        'estimated_arrival',
        'actual_arrival',
        'notes',
    ];

    protected $casts = [
        'estimated_arrival' => 'date',
        'actual_arrival' => 'date',
    ];

    public function po(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'po_type', 'po_id');
    }
}
