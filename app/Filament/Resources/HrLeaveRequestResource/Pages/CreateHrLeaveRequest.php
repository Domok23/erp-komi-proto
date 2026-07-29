<?php

namespace App\Filament\Resources\HrLeaveRequestResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateHrLeaveRequest extends CreateRecord
{
    protected static string $resource = 'App\Filament\Resources\HrLeaveRequestResource';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source'] = 'hrd';
        $data['status'] = 'pending';
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return $data;
    }
}
