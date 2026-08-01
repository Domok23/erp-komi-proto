<?php

namespace App\Filament\Resources\HrCandidateResource\Pages;

use Filament\Resources\Pages\CreateRecord;

class CreateHrCandidate extends CreateRecord
{
    protected static string $resource = 'App\Filament\Resources\HrCandidateResource';

    protected function afterCreate(): void
    {
        $this->record->seedDefaultChecklist();
    }
}
