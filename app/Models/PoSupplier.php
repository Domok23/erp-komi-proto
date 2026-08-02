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

    protected $table = 'po_suppliers';

    protected $fillable = [
        'company_id',
        'po_number',
        'project_id',
        'sub_project_id',
        'supplier_id',
        'po_date',
        'delivery_date',
        'status',
        'subtotal',
        'ppn_percent',
        'ppn_amount',
        'grand_total',
        'notes',
    ];

    protected $casts = [
        'po_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'ppn_percent' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function subProject(): BelongsTo
    {
        return $this->belongsTo(SubProject::class, 'sub_project_id');
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
