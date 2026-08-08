<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoSupplierApproval extends Model
{
    protected $table = 'po_supplier_approvals';

    protected $fillable = [
        'po_supplier_id',
        'approval_level',
        'status',
        'user_id',
        'signature_path',
        'rejection_reason',
        'actioned_at',
    ];

    protected $casts = [
        'actioned_at' => 'datetime',
    ];

    public function poSupplier(): BelongsTo
    {
        return $this->belongsTo(PoSupplier::class, 'po_supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
