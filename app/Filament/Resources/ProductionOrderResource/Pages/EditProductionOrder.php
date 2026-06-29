<?php
namespace App\Filament\Resources\ProductionOrderResource\Pages;
use App\Filament\Resources\ProductionOrderResource;
use App\Models\ProductionOrderMaterial;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionOrder extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\ProductionOrderResource';
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        
        if ($record->merchandisingPlanning) {
            $items = $record->merchandisingPlanning->items;
            $existingMaterials = $record->materials->keyBy('merchandising_planning_item_id');
            
            $materials = [];
            foreach ($items as $item) {
                if (!$item->material_id) {
                    continue;
                }

                $material = $item->material;
                $supplier = $item->supplier;
                $totalPrice = $item->planned_qty * $item->unit_price;
                $existingMaterial = $existingMaterials->get($item->id);
                
                $materials[] = [
                    'is_selected' => $existingMaterial ? $existingMaterial->is_selected : true,
                    'material_name' => $material ? $material->name : 'N/A',
                    'supplier_name' => $supplier ? $supplier->name : 'N/A',
                    'planned_qty' => $item->planned_qty,
                    'unit' => $item->unit,
                    'unit_price' => number_format($item->unit_price, 2, '.', ','),
                    'total_price' => number_format($totalPrice, 2, '.', ','),
                    'material_id' => $item->material_id,
                    'merchandising_planning_item_id' => $item->id,
                ];
            }
            $data['materials'] = $materials;
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        ProductionOrderMaterial::where('production_order_id', $record->id)->delete();

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
