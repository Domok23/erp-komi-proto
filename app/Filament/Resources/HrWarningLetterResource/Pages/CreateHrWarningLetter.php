<?php

namespace App\Filament\Resources\HrWarningLetterResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateHrWarningLetter extends CreateRecord
{
    protected static string $resource = 'App\Filament\Resources\HrWarningLetterResource';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
