<?php
namespace App\Filament\Resources\ProductionOrderResource\Pages;
use App\Filament\Resources\ProductionOrderResource;
use App\Models\ProductionOrderMaterial;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionOrder extends CreateRecord
{
    protected static string $resource = 'App\Filament\Resources\ProductionOrderResource';

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        if (isset($data['materials']) && is_array($data['materials'])) {
            foreach ($data['materials'] as $material) {
                if (!empty($material['material_id'])) {
                    ProductionOrderMaterial::create([
                        'company_id' => $record->company_id,
                        'production_order_id' => $record->id,
                        'merchandising_planning_item_id' => $material['merchandising_planning_item_id'] ?? null,
                        'material_id' => $material['material_id'],
                        'planned_qty' => $material['planned_qty'] ?? 0,
                        'unit' => $material['unit'] ?? 'pcs',
                        'is_selected' => true,
                    ]);
                }
            }
        }
    }
}
