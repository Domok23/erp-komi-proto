<?php

namespace App\Filament\Resources\MaterialUsageResource\Pages;

use App\Models\JobOrderMaterial;
use App\Models\MaterialLeftover;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialUsage extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\MaterialUsageResource';

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        $materials = [];
        $material = $record->material;

        $jobOrderMaterial = JobOrderMaterial::where('job_order_id', $record->job_order_id)
            ->where('material_id', $record->material_id)
            ->first();

        $materials[] = [
            'material_name' => $material ? $material->name : 'N/A',
            'planned_qty' => (float) $record->planned_qty,
            'unit' => $record->unit,
            'unit_price' => $record->unit_price,
            'total_cost' => $record->total_cost,
            'actual_qty' => (float) $record->actual_qty,
            'waste_qty' => (float) $record->waste_qty,
            'material_id' => $record->material_id,
            'merchandising_planning_item_id' => $jobOrderMaterial?->merchandising_planning_item_id,
        ];

        $data['materials'] = $materials;

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        $materialData = isset($data['materials']) && is_array($data['materials']) ? ($data['materials'][0] ?? []) : [];

        // Recalculate total cost
        $actualQty = $materialData['actual_qty'] ?? 0;
        $unitPrice = $materialData['unit_price'] ?? 0;
        $totalCost = $actualQty * $unitPrice;
        $record->update(['total_cost' => $totalCost]);

        // Recalculate leftover
        $plannedQty = $materialData['planned_qty'] ?? 0;
        $wasteQty = $materialData['waste_qty'] ?? 0;
        $leftoverQty = $plannedQty - $actualQty - $wasteQty;

        // Delete existing leftover for this material usage
        MaterialLeftover::where('job_order_id', $record->job_order_id)
            ->where('material_id', $record->material_id)
            ->where('notes', 'Auto-generated from material usage')
            ->delete();

        // Create new leftover if there's leftover
        if ($leftoverQty > 0) {
            MaterialLeftover::create([
                'company_id' => $record->company_id,
                'job_order_id' => $record->job_order_id,
                'material_id' => $record->material_id,
                'leftover_date' => $data['usage_date'] ?? now(),
                'qty' => $leftoverQty,
                'unit' => $materialData['unit'] ?? 'pcs',
                'condition' => 'usable',
                'status' => 'available',
                'notes' => 'Auto-generated from material usage',
            ]);
        }
    }
}
