<?php
namespace App\Filament\Resources\JobOrderResource\Pages;
use App\Filament\Resources\JobOrderResource;
use App\Models\JobOrderMaterial;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobOrder extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\JobOrderResource';
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

                $existingMaterial = $existingMaterials->get($item->id);
                
                // Only include materials that were actually selected/saved
                if ($existingMaterial && $existingMaterial->is_selected) {
                    $material = $item->material;
                    $supplier = $item->supplier;
                    $totalPrice = $item->planned_qty * $item->unit_price;
                    
                    $materials[] = [
                        'is_selected' => true,
                        'material_name' => $material ? $material->name : 'N/A',
                        'supplier_name' => $supplier ? $supplier->name : 'N/A',
                        'planned_qty' => $item->planned_qty,
                        'unit' => $item->unit,
                        'unit_price' => number_format($item->unit_price, 0),
                        'total_price' => number_format($totalPrice, 0),
                        'material_id' => $item->material_id,
                        'merchandising_planning_item_id' => $item->id,
                    ];
                }
            }
            $data['materials'] = $materials;
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        JobOrderMaterial::where('job_order_id', $record->id)->delete();

        if (isset($data['materials']) && is_array($data['materials'])) {
            foreach ($data['materials'] as $material) {
                if (isset($material['is_selected']) && $material['is_selected'] && !empty($material['material_id'])) {
                    JobOrderMaterial::create([
                        'company_id' => $record->company_id,
                        'job_order_id' => $record->id,
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
