<?php

namespace Tests\Feature;

use App\Filament\Actions\PickMaterialsAction;
use Tests\TestCase;

class PickMaterialsActionTest extends TestCase
{
    public function test_pick_materials_action_can_be_instantiated(): void
    {
        $action = PickMaterialsAction::make('browse_materials')
            ->repeaterName('items')
            ->itemHydrator(fn (array $material) => [
                'material_id' => $material['id'],
                'unit' => $material['uom'],
            ]);

        $this->assertEquals('browse_materials', $action->getName());
        $this->assertEquals('Browse Material Catalog', $action->getLabel());
        $this->assertEquals('items', $action->getRepeaterName());
    }
}
