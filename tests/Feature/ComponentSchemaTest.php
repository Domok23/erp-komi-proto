<?php

namespace Tests\Feature;

use App\Models\ConsumptionRate;
use App\Models\BomItem;
use App\Models\MerchandisePlanningItem;
use App\Models\PoSupplierItem;
use App\Models\PoSubconItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_field_is_fillable_on_models(): void
    {
        $cr = new ConsumptionRate(['component' => 'Body']);
        $this->assertEquals('Body', $cr->component);

        $bomItem = new BomItem(['component' => 'Lining']);
        $this->assertEquals('Lining', $bomItem->component);

        $mpItem = new MerchandisePlanningItem(['component' => 'Handle']);
        $this->assertEquals('Handle', $mpItem->component);

        $poSupplierItem = new PoSupplierItem(['component' => 'Zipper']);
        $this->assertEquals('Zipper', $poSupplierItem->component);

        $poSubconItem = new PoSubconItem(['component' => 'Strap']);
        $this->assertEquals('Strap', $poSubconItem->component);
    }
}
