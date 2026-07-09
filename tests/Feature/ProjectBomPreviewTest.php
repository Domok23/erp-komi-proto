<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Company;
use App\Models\Material;
use App\Models\RdDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectBomPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_load_bom_items_helper_populates_state(): void
    {
        $company = Company::create([
            'name' => 'PT Komitrando Emporio Test',
            'code' => 'KOMI-TEST',
            'address' => 'Jogja',
        ]);

        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DSN-TEST',
            'name' => 'Test Design',
            'product_type' => 'jacket',
            'status' => 'approved',
            'estimated_material_cost' => 100000,
        ]);

        $bom = Bom::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'name' => 'BOM Alpha',
            'version' => '1.0',
            'status' => 'active',
        ]);

        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-TEST',
            'name' => 'Test Fabric',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 5000,
            'stock' => 0,
        ]);

        BomItem::create([
            'bom_id' => $bom->id,
            'material_id' => $material->id,
            'category' => 'main_material',
            'quantity_per_unit' => 1.5000,
            'unit' => 'yard',
            'wastage_percent' => 5.00,
            'is_from_rnd' => true,
        ]);

        $state = $bom->id;
        $bomItems = null;

        $set = function ($key, $value) use (&$bomItems) {
            if ($key === 'bom_items') {
                $bomItems = $value;
            }
        };

        ProjectResource::loadBomItems($state, $set);

        $this->assertNotNull($bomItems);
        $this->assertCount(1, $bomItems);
        $this->assertEquals('Test Fabric', $bomItems[0]['material_name']);
        $this->assertEquals('main_material', $bomItems[0]['category']);
        $this->assertEquals(1.5, $bomItems[0]['quantity_per_unit']);
        $this->assertEquals('yard', $bomItems[0]['unit']);
        $this->assertEquals(5.0, $bomItems[0]['wastage_percent']);
    }

    public function test_load_bom_items_clears_state_when_null(): void
    {
        $bomItems = null;
        $set = function ($key, $value) use (&$bomItems) {
            if ($key === 'bom_items') {
                $bomItems = $value;
            }
        };

        ProjectResource::loadBomItems(null, $set);

        $this->assertEquals([], $bomItems);
    }
}
