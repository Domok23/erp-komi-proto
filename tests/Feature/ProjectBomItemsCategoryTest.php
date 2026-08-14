<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Company;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\RdDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectBomItemsCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_load_bom_items_retrieves_correct_category(): void
    {
        $company = Company::create([
            'name' => 'Category Test Co',
            'code' => 'CTC',
            'address' => 'Test Address',
        ]);

        $category = MaterialCategory::create([
            'company_id' => $company->id,
            'code' => 'fabric',
            'name' => 'Main Fabric',
        ]);

        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-CAT-01',
            'name' => 'Canvas Fabric',
            'category_id' => $category->id,
            'category' => 'Main Fabric',
            'uom' => 'yard',
            'price' => 15000,
        ]);

        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DES-CAT-01',
            'name' => 'Bag Design',
            'product_type' => 'jacket',
            'status' => 'approved',
        ]);

        $bom = Bom::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'bom_number' => 'BOM-CAT-01',
            'name' => 'BOM Category Test',
            'status' => 'active',
        ]);

        $bomItem = BomItem::create([
            'bom_id' => $bom->id,
            'material_id' => $material->id,
            'quantity_per_unit' => 2.5,
            'unit' => 'yard',
            'wastage_percent' => 3,
        ]);

        $capturedItems = [];
        ProjectResource::loadBomItems($bom->id, function ($key, $items) use (&$capturedItems) {
            $capturedItems = $items;
        });

        $this->assertNotEmpty($capturedItems);
        $this->assertEquals('Canvas Fabric', $capturedItems[0]['material_name']);
        $this->assertEquals('Main Fabric', $capturedItems[0]['category']);
        $this->assertEquals('yard', $capturedItems[0]['unit']);
    }
}
