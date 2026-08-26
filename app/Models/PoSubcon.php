<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoSubcon extends Model
{
    use BelongsToCompany;

    protected $table = 'po_subcons';

    protected $fillable = [
        'company_id',
        'po_number',
        'project_id',
        'project_ids',
        'subcon_id',
        'po_date',
        'delivery_date',
        'status',
        'service_cost',
        'shipping_cost',
        'shipping_return_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'project_ids' => 'array',
        'po_date' => 'date',
        'delivery_date' => 'date',
        'service_cost' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'shipping_return_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function getProjectsAttribute()
    {
        $ids = $this->project_ids ?? ($this->project_id ? [$this->project_id] : []);

        return Project::whereIn('id', $ids)->get();
    }

    public function getProjectNamesAttribute(): string
    {
        $projects = $this->projects;
        if ($projects->isEmpty()) {
            return $this->project->name ?? '-';
        }

        return $projects->pluck('name')->implode(', ');
    }

    public function subcon(): BelongsTo
    {
        return $this->belongsTo(Subcon::class, 'subcon_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PoSubconItem::class, 'po_subcon_id');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'po_id')->where('po_type', 'subcon');
    }

    public function purchaseShipments(): HasMany
    {
        return $this->hasMany(PurchaseShipment::class, 'po_id')->where('po_type', 'subcon');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoicePurchase::class, 'reference_id')->where('purchase_type', 'po_subcon');
    }

    public function recalculateTotals(): void
    {
        $totalServiceCost = (float) $this->items()->sum('total_price');
        $this->updateQuietly([
            'service_cost' => $totalServiceCost,
            'total_cost' => $totalServiceCost + (float) ($this->shipping_cost ?? 0) + (float) ($this->shipping_return_cost ?? 0),
        ]);
    }
}
