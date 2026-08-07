<?php

namespace App\Observers;

use App\Models\InventoryStock;
use App\Models\MaterialReservation;

class MaterialReservationObserver
{
    /**
     * Handle the MaterialReservation "saved" event.
     */
    public function saved(MaterialReservation $reservation): void
    {
        $this->syncInventoryStock($reservation);
    }

    /**
     * Handle the MaterialReservation "deleted" event.
     */
    public function deleted(MaterialReservation $reservation): void
    {
        $this->syncInventoryStock($reservation, true);
    }

    /**
     * Sync inventory stock reserved_qty and available_qty.
     */
    protected function syncInventoryStock(MaterialReservation $reservation, bool $isDeleting = false): void
    {
        // Only update inventory stock when status is approved
        if ($reservation->status !== 'approved') {
            return;
        }

        $stock = InventoryStock::where('company_id', $reservation->company_id)
            ->where('warehouse_id', $reservation->warehouse_id)
            ->where('material_id', $reservation->material_id)
            ->first();

        if (! $stock) {
            return;
        }

        // Calculate total reserved qty for this material/warehouse/company
        $totalReserved = MaterialReservation::where('company_id', $reservation->company_id)
            ->where('warehouse_id', $reservation->warehouse_id)
            ->where('material_id', $reservation->material_id)
            ->where('status', 'approved')
            ->sum('reserved_qty');

        // Update inventory stock
        $stock->update([
            'reserved_qty' => $totalReserved,
            'available_qty' => $stock->quantity - $totalReserved,
        ]);
    }
}
