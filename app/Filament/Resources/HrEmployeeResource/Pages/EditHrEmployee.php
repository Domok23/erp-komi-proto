<?php

namespace App\Filament\Resources\HrEmployeeResource\Pages;

use App\Services\PlaceEmployeeService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditHrEmployee extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\HrEmployeeResource';

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (($data['status'] ?? $record->status) === 'inactive' && $record->status === 'active') {
            unset($data['status']);
            $record->update($data);

            PlaceEmployeeService::deactivate($record->fresh(), Auth::id());

            return $record->fresh();
        }

        $record->update($data);

        return $record;
    }
}
