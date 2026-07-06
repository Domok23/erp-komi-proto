<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\StateCasts\BooleanStateCast;

class NullableToggle extends Toggle
{
    public function getDefaultStateCasts(): array
    {
        return [
            app(BooleanStateCast::class, ['isNullable' => true]),
        ];
    }
}
