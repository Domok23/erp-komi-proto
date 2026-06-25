<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Company;
use App\Models\RdDesign;
use App\Models\Material;
use App\Models\ConsumptionRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsumptionRatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumption_rates_can_be_associated_with_design(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'address' => 'Test Address',
        ]);
        
        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DES-001',
            'name' => 'Test Design',
            'bag_type' => 'backpack',
            'status' => 'draft',
        ]);
        
        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-001',
            'name' => 'Test Material',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 1000,
            'stock' => 0,
        ]);
        
        $rate = ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $material->id,
            'standard_rate' => 2.5,
            'unit' => 'yard',
            'wastage_rate' => 10,
            'notes' => 'Test Notes',
        ]);
        
        $this->assertEquals($design->id, $rate->design_id);
        $this->assertEquals($material->id, $rate->material_id);
        $this->assertEquals(2.5, $rate->standard_rate);
        $this->assertEquals('yard', $rate->unit);
        $this->assertEquals(10, $rate->wastage_rate);
        
        // Assert relationships
        $this->assertCount(1, $design->consumptionRates);
        $this->assertEquals($rate->id, $design->consumptionRates->first()->id);
    }

    public function test_bom_mapping_logic_from_consumption_rates(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'address' => 'Test Address',
        ]);
        
        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DES-001',
            'name' => 'Test Design',
            'bag_type' => 'backpack',
            'status' => 'draft',
        ]);
        
        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-001',
            'name' => 'Test Material',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 1000,
            'stock' => 0,
        ]);
        
        ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $material->id,
            'standard_rate' => 2.5,
            'unit' => 'yard',
            'wastage_rate' => 10,
            'notes' => 'Test Notes',
        ]);

        // Simulating the afterStateUpdated trigger callback
        $rates = ConsumptionRate::where('design_id', $design->id)->get();
        $items = $rates->map(function ($rate) {
            return [
                'material_id' => $rate->material_id,
                'category' => 'main_material',
                'quantity_per_unit' => $rate->standard_rate,
                'unit' => $rate->unit,
                'wastage_percent' => $rate->wastage_rate,
                'notes' => $rate->notes,
            ];
        })->toArray();

        $this->assertCount(1, $items);
        $this->assertEquals($material->id, $items[0]['material_id']);
        $this->assertEquals('main_material', $items[0]['category']);
        $this->assertEquals(2.5, $items[0]['quantity_per_unit']);
        $this->assertEquals('yard', $items[0]['unit']);
        $this->assertEquals(10, $items[0]['wastage_percent']);
        $this->assertEquals('Test Notes', $items[0]['notes']);
    }

    public function test_design_cost_estimation_triggers_on_consumption_rate_change(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'address' => 'Test Address',
        ]);
        
        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DES-001',
            'name' => 'Test Design',
            'bag_type' => 'backpack',
            'status' => 'draft',
        ]);
        
        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-001',
            'name' => 'Test Material',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 10000,
            'stock' => 0,
        ]);

        // 1. Test creation trigger
        $rate = ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $material->id,
            'standard_rate' => 2.5,
            'unit' => 'yard',
            'wastage_rate' => 10,
            'notes' => 'Test Notes',
        ]);

        $design->refresh();
        $this->assertEquals(27500, $design->estimated_material_cost);
        $this->assertEquals(33000, $design->estimated_mp_cost);
        $this->assertEquals(83490, $design->estimated_selling_price);

        // 2. Test update trigger
        $rate->update([
            'standard_rate' => 3.5,
        ]);

        $design->refresh();
        // 3.5 * 1.10 * 10000 = 38500
        // (38500 + 33000) * 1.15 * 1.20 = 71500 * 1.15 * 1.20 = 98670
        $this->assertEquals(38500, $design->estimated_material_cost);
        $this->assertEquals(98670, $design->estimated_selling_price);

        // 3. Test deletion trigger
        $rate->delete();

        $design->refresh();
        // 0 material cost
        // (0 + 33000) * 1.15 * 1.20 = 33000 * 1.15 * 1.20 = 45540
        $this->assertEquals(0, $design->estimated_material_cost);
        $this->assertEquals(45540, $design->estimated_selling_price);
    }
}
