<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoSubconItem extends Model
{
    protected $table = 'po_subcon_items';

    protected $fillable = [
        'po_subcon_id',
        'project_id',
        'sub_project_id',
        'description',
        'qty',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function poSubcon(): BelongsTo
    {
        return $this->belongsTo(PoSubcon::class, 'po_subcon_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function subProject(): BelongsTo
    {
        return $this->belongsTo(SubProject::class, 'sub_project_id');
    }
}
