<?php

namespace Tests\Feature;

use App\Filament\Resources\RdDesignResource\RelationManagers\ConsumptionRatesRelationManager;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Company;
use App\Models\ConsumptionRate;
use App\Models\Material;
use App\Models\RdDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
            'product_type' => 'jacket',
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
            'product_type' => 'jacket',
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
                'is_from_rnd' => true,
            ];
        })->toArray();

        $this->assertCount(1, $items);
        $this->assertEquals($material->id, $items[0]['material_id']);
        $this->assertEquals('main_material', $items[0]['category']);
        $this->assertEquals(2.5, $items[0]['quantity_per_unit']);
        $this->assertEquals('yard', $items[0]['unit']);
        $this->assertEquals(10, $items[0]['wastage_percent']);
        $this->assertEquals('Test Notes', $items[0]['notes']);
        $this->assertTrue($items[0]['is_from_rnd']);
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
            'product_type' => 'jacket',
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

    public function test_decimal_sanitization(): void
    {
        $managerClass = ConsumptionRatesRelationManager::class;

        // Test normalizeDecimal with various formats
        $this->assertEquals(1.5, $managerClass::normalizeDecimal('1.5'));
        $this->assertEquals(1.5, $managerClass::normalizeDecimal('1,5'));
        $this->assertEquals(1500.5, $managerClass::normalizeDecimal('1.500,50'));
        $this->assertEquals(1500.5, $managerClass::normalizeDecimal('1,500.50'));
        $this->assertEquals(1500.0, $managerClass::normalizeDecimal('1,500'));
        $this->assertNull($managerClass::normalizeDecimal(''));
        $this->assertEquals(10.5, $managerClass::normalizeDecimal('10.5'));
        $this->assertEquals(10.5, $managerClass::normalizeDecimal('10,5'));
    }

    public function test_consumption_rate_syncs_with_existing_bom_items(): void
    {
        $company = Company::create([
            'name' => 'Sync Test Co',
            'code' => 'STC',
            'address' => 'Test Address',
        ]);

        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DES-SYNC-01',
            'name' => 'Sync Design',
            'product_type' => 'jacket',
            'status' => 'approved',
        ]);

        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-SYNC-01',
            'name' => 'Sync Material',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 5000,
            'stock' => 0,
        ]);

        $bom = Bom::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'bom_number' => 'BOM-SYNC-01',
            'name' => 'BOM Sync Test',
            'version' => '1.0',
            'status' => 'draft',
        ]);

        // Create initial consumption rate
        $rate = ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $material->id,
            'component' => 'Front Pocket',
            'standard_rate' => 2.0,
            'unit' => 'yard',
            'wastage_rate' => 5,
        ]);

        // Assert BOM item was automatically created with component
        $bomItem = BomItem::where('bom_id', $bom->id)->where('material_id', $material->id)->first();
        $this->assertNotNull($bomItem);
        $this->assertEquals('Front Pocket', $bomItem->component);

        // Update consumption rate component
        $rate->update(['component' => 'Back Pocket & Flap']);

        // Assert BOM item component was automatically updated
        $bomItem->refresh();
        $this->assertEquals('Back Pocket & Flap', $bomItem->component);
    }

    public function test_multi_component_rates_create_distinct_bom_items_and_delete_cleanly(): void
    {
        $company = Company::create([
            'name' => 'Multi Comp Co',
            'code' => 'MCC',
            'address' => 'Test Address',
        ]);

        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DES-MC-01',
            'name' => 'Multi Component Design',
            'product_type' => 'jacket',
            'status' => 'approved',
        ]);

        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-MC-01',
            'name' => 'Leather Fabric',
            'category' => 'fabric',
            'unit' => 'sqft',
            'price' => 50000,
            'stock' => 100,
        ]);

        $bom = Bom::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'bom_number' => 'BOM-MC-01',
            'name' => 'BOM Multi Test',
            'version' => '1.0',
            'status' => 'draft',
        ]);

        // Create 2 rates for the same material with different components
        $rate1 = ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $material->id,
            'component' => 'Body',
            'standard_rate' => 3.0,
            'unit' => 'sqft',
            'wastage_rate' => 3,
        ]);

        $rate2 = ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $material->id,
            'component' => 'Front Pocket',
            'standard_rate' => 0.5,
            'unit' => 'sqft',
            'wastage_rate' => 3,
        ]);

        $bomItems = BomItem::where('bom_id', $bom->id)->where('material_id', $material->id)->get();
        $this->assertCount(2, $bomItems);
        $this->assertTrue($bomItems->contains('component', 'Body'));
        $this->assertTrue($bomItems->contains('component', 'Front Pocket'));

        // Deleting rate1 should only delete the 'Body' bom item
        $rate1->delete();

        $bomItemsRemaining = BomItem::where('bom_id', $bom->id)->where('material_id', $material->id)->get();
        $this->assertCount(1, $bomItemsRemaining);
        $this->assertEquals('Front Pocket', $bomItemsRemaining->first()->component);
    }
}
