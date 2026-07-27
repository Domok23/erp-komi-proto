<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\IconSize;

class StockPreviewAction
{
    public static function make(string $context = 'form'): Action
    {
        $action = Action::make('stock_preview')
            ->label('Preview Stock')
            ->icon('heroicon-o-eye')
            ->iconSize(IconSize::Medium)
            ->modalHeading('Stock Preview')
            ->modalSubmitAction(false);

        if ($context === 'form') {
            $action->color('info');
        } else {
            $action->iconButton()->tooltip('Preview stock for this material');
        }

        return $action;
    }
}
