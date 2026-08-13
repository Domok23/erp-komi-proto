<?php

namespace Tests\Feature;

use App\Filament\Actions\StockPreviewAction;
use Filament\Actions\Action;
use Tests\TestCase;

class StockPreviewActionTest extends TestCase
{
    public function test_action_can_be_created(): void
    {
        $action = StockPreviewAction::make('form');
        $this->assertInstanceOf(Action::class, $action);
        $this->assertSame('stock_preview', $action->getName());
    }
}
