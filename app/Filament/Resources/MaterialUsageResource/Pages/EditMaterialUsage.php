<?php
namespace App\Filament\Resources\MaterialUsageResource\Pages;
use App\Filament\Resources\MaterialUsageResource;
use App\Models\MaterialUsage;
use App\Models\MaterialLeftover;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialUsage extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\MaterialUsageResource';
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }

    protected function afterSave(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        // Recalculate total cost
        $actualQty = $data['actual_qty'] ?? 0;
        $unitPrice = $data['unit_price'] ?? 0;
        $totalCost = $actualQty * $unitPrice;
        $record->update(['total_cost' => $totalCost]);

        // Recalculate leftover
        $plannedQty = $data['planned_qty'] ?? 0;
        $wasteQty = $data['waste_qty'] ?? 0;
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
                'unit' => $data['unit'] ?? 'pcs',
                'condition' => 'usable',
                'status' => 'available',
                'notes' => 'Auto-generated from material usage',
            ]);
        }
    }
}
