<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeliveryAlertLog extends Model
{
    use BelongsToCompany;

    protected $table = 'delivery_alert_logs';

    protected $fillable = [
        'company_id',
        'alertable_type',
        'alertable_id',
        'alert_level',
        'deadline_date',
        'days_overdue',
        'sent_at',
    ];

    protected $casts = [
        'deadline_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }
}
