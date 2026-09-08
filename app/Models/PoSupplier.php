<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PoSupplier extends Model
{
    use BelongsToCompany;

    public const DIRECTOR_APPROVAL_THRESHOLD = 100000000.0;

    protected $table = 'po_suppliers';

    protected $fillable = [
        'company_id',
        'po_number',
        'project_id',
        'project_ids',
        'supplier_id',
        'po_date',
        'delivery_date',
        'status',
        'approval_status',
        'parent_id',
        'revision_number',
        'subtotal',
        'ppn_percent',
        'ppn_amount',
        'grand_total',
        'notes',
        'buyer_signature',
    ];

    protected $casts = [
        'project_ids' => 'array',
        'po_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'ppn_percent' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'revision_number' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function getProjectsAttribute()
    {
        if ($this->relationLoaded('items')) {
            $projects = $this->items->pluck('project')->filter()->unique('id')->values();
            if ($projects->isNotEmpty()) {
                return $projects;
            }
        }

        if ($this->relationLoaded('project') && $this->project) {
            return collect([$this->project]);
        }

        $ids = $this->project_ids ?? ($this->project_id ? [$this->project_id] : []);

        if (empty($ids)) {
            return collect();
        }

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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PoSupplierItem::class, 'po_supplier_id');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'po_id');
    }

    public function purchaseShipments(): HasMany
    {
        return $this->hasMany(PurchaseShipment::class, 'po_id')->where('po_type', 'supplier');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoicePurchase::class, 'reference_id')->where('purchase_type', 'po_supplier');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PoSupplierApproval::class, 'po_supplier_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PoSupplier::class, 'parent_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PoSupplier::class, 'parent_id');
    }

    public function getDeletionBlockers(): array
    {
        $blockers = [];

        if (in_array($this->status, ['ordered', 'partially_received', 'received'])) {
            $blockers[] = "status is '{$this->status}' (active or received orders cannot be deleted)";
        }

        if ($this->approval_status === 'approved') {
            $blockers[] = "approval status is 'approved' (approved POs cannot be deleted; cancel or revise instead)";
        }

        $grCount = $this->goodsReceipts()->count();
        if ($grCount > 0) {
            $blockers[] = "{$grCount} linked Goods Receipt(s)";
        }

        $shipmentCount = $this->purchaseShipments()->count();
        if ($shipmentCount > 0) {
            $blockers[] = "{$shipmentCount} linked Purchase Shipment(s)";
        }

        $invCount = $this->invoices()->count();
        if ($invCount > 0) {
            $blockers[] = "{$invCount} linked Purchase Invoice(s)";
        }

        $revCount = $this->revisions()->count();
        if ($revCount > 0) {
            $blockers[] = "{$revCount} linked Revision(s)";
        }

        return $blockers;
    }

    public function syncReceivedQty(): void
    {
        $poSupplierId = $this->id;
        $grIds = GoodsReceipt::where('po_type', 'supplier')
            ->where('po_id', $poSupplierId)
            ->pluck('id');

        $grItems = GoodsReceiptItem::whereIn('goods_receipt_id', $grIds)
            ->select('material_id', DB::raw('SUM(qty_received) as total_received'))
            ->groupBy('material_id')
            ->pluck('total_received', 'material_id');

        foreach ($this->items as $item) {
            $received = $grItems->get($item->material_id, 0);
            $item->updateQuietly(['qty_received' => $received]);
            $item->qty_received = $received;
        }
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items->sum('total_price');
        $ppnAmount = $subtotal * ($this->ppn_percent / 100);

        $this->updateQuietly([
            'subtotal' => $subtotal,
            'ppn_amount' => $ppnAmount,
            'grand_total' => $subtotal + $ppnAmount,
        ]);
    }

    public function syncStatusFromItems(): void
    {
        if (! in_array($this->status, ['ordered', 'partial'])) {
            return;
        }

        $items = $this->items;
        if ($items->isEmpty()) {
            return;
        }

        $allReceived = $items->every(fn ($item) => $item->qty_received >= $item->qty);
        $anyReceived = $items->contains(fn ($item) => $item->qty_received > 0);

        if ($allReceived) {
            $this->updateQuietly(['status' => 'received']);
        } elseif ($anyReceived) {
            $this->updateQuietly(['status' => 'partial']);
        }
    }
}
