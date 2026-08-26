<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\ConsumptionRate;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialUom;
use App\Models\RdDesign;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RdDesignRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_revision_and_clone_consumption_rates(): void
    {
        $company = Company::create(['name' => 'Test Company', 'code' => 'TC']);
        session(['active_company_id' => $company->id]);

        $uom = MaterialUom::create(['company_id' => $company->id, 'name' => 'Meter', 'code' => 'MTR']);
        $cat = MaterialCategory::create(['company_id' => $company->id, 'name' => 'Fabric', 'code' => 'FAB']);
        $supplier = Supplier::create(['company_id' => $company->id, 'name' => 'Test Supplier', 'code' => 'SUP01']);

        $mat = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT01',
            'name' => 'Canvas Fabric',
            'category' => 'Fabric',
            'category_id' => $cat->id,
            'uom_id' => $uom->id,
            'supplier_id' => $supplier->id,
            'price' => 50000,
        ]);

        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DSG001',
            'name' => 'Tote Bag Deluxe',
            'version' => '1.0',
            'status' => 'approved',
            'product_type' => 'tote_bag',
        ]);

        ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $mat->id,
            'component' => 'Body Main',
            'standard_rate' => 0.5,
            'unit' => 'MTR',
            'wastage_rate' => 5,
        ]);

        $revision = $design->createRevision('1.1');

        $this->assertEquals('1.1', $revision->version);
        $this->assertEquals('draft', $revision->status);
        $this->assertEquals($design->id, $revision->parent_design_id);
        $this->assertCount(1, $revision->consumptionRates);
        $this->assertEquals('Body Main', $revision->consumptionRates->first()->component);
        $this->assertEquals(0.5, (float) $revision->consumptionRates->first()->standard_rate);
    }
}
