<div 
    x-data="{
        repeaterName: @js($repeaterName),
        handleMaterialsPicked(event) {
            const materials = event.detail.materials || [];
            if (!materials.length) return;

            // Retrieve current repeater state from Livewire
            let currentItems = $wire.get('data.' + this.repeaterName) || [];
            let newItems = Array.isArray(currentItems) ? [...currentItems] : Object.values(currentItems);

            materials.forEach(mat => {
                const formattedPrice = (mat.price || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                newItems.push({
                    material_id: mat.id,
                    unit: mat.uom || 'pcs',
                    unit_price: formattedPrice,
                    qty: 1,
                    qty_sent: 1,
                    planned_qty: 1,
                    quantity_per_unit: 1.0000,
                    wastage_percent: 3,
                    supplier_id: mat.supplier_id || null,
                    total_price: formattedPrice,
                    is_from_rnd: false,
                });
            });

            $wire.set('data.' + this.repeaterName, newItems);
            $dispatch('close-modal', { id: 'browse_materials' });
        }
    }"
    x-on:materials-picked.window="handleMaterialsPicked($event)"
>
    @livewire('components.material-picker-modal', [
        'supplierId' => $supplierId,
        'warehouseId' => $warehouseId,
        'onlyInStock' => $onlyInStock,
        'alreadyAddedIds' => $alreadyAddedIds,
        'mode' => 'bulk',
    ])
</div>
