<?php
namespace App\Filament\Resources\MaterialUsageResource\Pages;
use App\Filament\Resources\MaterialUsageResource;
use App\Models\MaterialUsage;
use App\Models\MaterialLeftover;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterialUsage extends CreateRecord
{
    protected static string $resource = 'App\Filament\Resources\MaterialUsageResource';

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        // Delete the parent record since we're creating individual material usage records
        $record->delete();

        if (isset($data['materials']) && is_array($data['materials'])) {
            foreach ($data['materials'] as $material) {
                if (empty($material['material_id'])) {
                    continue;
                }

                $plannedQty = (float)($material['planned_qty'] ?? 0);
                $actualQty = (float)($material['actual_qty'] ?? 0);
                $wasteQty = (float)($material['waste_qty'] ?? 0);
                $unitPrice = (float)($material['unit_price'] ?? 0);
                $totalCost = $actualQty * $unitPrice;
                $leftoverQty = max(0, $plannedQty - $actualQty - $wasteQty);

                // Create Material Usage record
                MaterialUsage::create([
                    'company_id' => $data['company_id'] ?? null,
                    'job_order_id' => $data['job_order_id'],
                    'material_id' => $material['material_id'] ?? null,
                    'usage_date' => $data['usage_date'] ?? now(),
                    'planned_qty' => $plannedQty,
                    'actual_qty' => $actualQty,
                    'waste_qty' => $wasteQty,
                    'unit' => $material['unit'] ?? 'pcs',
                    'unit_price' => $unitPrice,
                    'total_cost' => $totalCost,
                    'status' => $data['status'] ?? 'planned',
                    'notes' => $data['notes'] ?? null,
                ]);

                // Auto-create Material Leftover if there's leftover
                if ($leftoverQty > 0) {
                    MaterialLeftover::create([
                        'company_id' => $data['company_id'] ?? null,
                        'job_order_id' => $data['job_order_id'],
                        'material_id' => $material['material_id'] ?? null,
                        'leftover_date' => $data['usage_date'] ?? now(),
                        'qty' => $leftoverQty,
                        'unit' => $material['unit'] ?? 'pcs',
                        'condition' => 'usable',
                        'status' => 'available',
                        'notes' => 'Auto-generated from material usage',
                    ]);
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
