<?php

namespace App\Filament\Resources\CostingResource\Pages;

use App\Filament\Resources\CostingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCosting extends CreateRecord
{
    protected static string $resource = 'App\Filament\Resources\CostingResource';

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Fix #3: Only auto-calculate if ALL required costs are filled
        if ($record->status === 'draft' && $record->hasAllCostsFilled()) {
            $record->update(['status' => 'calculated']);
        }
    }
}
