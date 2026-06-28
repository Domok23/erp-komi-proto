<?php
namespace App\Filament\Resources\JobOrderResource\Pages;
use App\Filament\Resources\JobOrderResource;
use App\Models\JobOrderMaterial;
use Filament\Resources\Pages\CreateRecord;

class CreateJobOrder extends CreateRecord
{
    protected static string $resource = 'App\Filament\Resources\JobOrderResource';

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        if (isset($data['materials']) && is_array($data['materials'])) {
            foreach ($data['materials'] as $material) {
                if (isset($material['is_selected']) && $material['is_selected']) {
                    JobOrderMaterial::create([
                        'company_id' => $record->company_id,
                        'job_order_id' => $record->id,
                        'merchandising_planning_item_id' => $material['merchandising_planning_item_id'] ?? null,
                        'material_id' => $material['material_id'] ?? null,
                        'planned_qty' => is_numeric($material['planned_qty']) ? (float)$material['planned_qty'] : 0,
                        'unit' => $material['unit'] ?? 'pcs',
                        'is_selected' => true,
                    ]);
                }
            }
        }
    }
}
