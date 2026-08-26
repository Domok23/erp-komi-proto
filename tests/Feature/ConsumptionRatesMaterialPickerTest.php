<?php

namespace Tests\Feature;

use App\Filament\Actions\PickMaterialsAction;
use App\Livewire\Components\MaterialPickerModal;
use App\Models\Company;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\RdDesign;
use App\Models\Supplier;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsumptionRatesMaterialPickerTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected RdDesign $design;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'PT Test Komitrando',
            'code' => 'KOMITEST',
            'is_active' => true,
        ]);

        CompanyContext::setCompany($this->company);

        $this->design = RdDesign::create([
            'name' => 'Backpack Explorer 30L',
            'code' => 'DES-EXP30',
            'bag_type' => 'Backpack',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_pick_materials_action_supports_allocation_step_and_design_id(): void
    {
        $action = PickMaterialsAction::make()
            ->showAllocationStep(true)
            ->designId($this->design->id)
            ->alreadyAddedIds([10, 20]);

        $this->assertInstanceOf(PickMaterialsAction::class, $action);
    }

    public function test_modal_transitions_to_allocation_step_on_single_select(): void
    {
        $cat = MaterialCategory::create(['name' => 'Fabric', 'code' => 'FAB', 'company_id' => $this->company->id]);
        $sup = Supplier::create(['name' => 'Supplier F', 'code' => 'SUP-F', 'company_id' => $this->company->id]);
        $mat = Material::create([
            'code' => 'FAB-001',
            'name' => 'Cordura 1000D',
            'category' => 'fabric',
            'category_id' => $cat->id,
            'supplier_id' => $sup->id,
            'company_id' => $this->company->id,
            'uom' => 'yard',
            'price' => 50000,
        ]);

        Livewire::test(MaterialPickerModal::class, [
            'showAllocationStep' => true,
            'designId' => $this->design->id,
            'alreadyAddedIds' => [],
        ])
            ->assertSet('step', 'picker')
            ->call('singleSelect', $mat->id)
            ->assertSet('step', 'allocation')
            ->assertCount('selectedMaterials', 1)
            ->assertSet('selectedMaterials.0.code', 'FAB-001');
    }

    public function test_save_allocation_creates_consumption_rates_and_registers_component(): void
    {
        $cat = MaterialCategory::create(['name' => 'Zipper', 'code' => 'ZIP', 'company_id' => $this->company->id]);
        $sup = Supplier::create(['name' => 'Supplier Z', 'code' => 'SUP-Z', 'company_id' => $this->company->id]);
        $mat1 = Material::create([
            'code' => 'ZIP-001',
            'name' => 'YKK Zipper #5',
            'category' => 'zipper',
            'category_id' => $cat->id,
            'supplier_id' => $sup->id,
            'company_id' => $this->company->id,
            'uom' => 'pcs',
        ]);
        $mat2 = Material::create([
            'code' => 'ZIP-002',
            'name' => 'YKK Slider #5',
            'category' => 'zipper',
            'category_id' => $cat->id,
            'supplier_id' => $sup->id,
            'company_id' => $this->company->id,
            'uom' => 'pcs',
        ]);

        Livewire::test(MaterialPickerModal::class, [
            'showAllocationStep' => true,
            'designId' => $this->design->id,
            'alreadyAddedIds' => [],
        ])
            ->set('step', 'allocation')
            ->set('selectedMaterials', [
                [
                    'id' => $mat1->id,
                    'code' => $mat1->code,
                    'name' => $mat1->name,
                    'category' => 'Zipper',
                    'uom' => 'pcs',
                    'component' => 'Main Compartment',
                    'actual_consumption' => 1.5,
                    'notes' => 'Top zip closure',
                ],
                [
                    'id' => $mat2->id,
                    'code' => $mat2->code,
                    'name' => $mat2->name,
                    'category' => 'Zipper',
                    'uom' => 'pcs',
                    'component' => 'Front Pocket',
                    'actual_consumption' => 2.0,
                    'notes' => 'Double pull slider',
                ],
            ])
            ->call('saveAllocation')
            ->assertDispatched('close-modal')
            ->assertDispatched('refresh-consumption-rates');

        $this->assertDatabaseHas('consumption_rates', [
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'material_id' => $mat1->id,
            'component' => 'Main Compartment',
            'standard_rate' => 1.5,
            'notes' => 'Top zip closure',
        ]);

        $this->assertDatabaseHas('consumption_rates', [
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'material_id' => $mat2->id,
            'component' => 'Front Pocket',
            'standard_rate' => 2.0,
            'notes' => 'Double pull slider',
        ]);

        // Auto-registered components
        $this->assertDatabaseHas('components', [
            'company_id' => $this->company->id,
            'name' => 'Main Compartment',
        ]);
        $this->assertDatabaseHas('components', [
            'company_id' => $this->company->id,
            'name' => 'Front Pocket',
        ]);
    }

    public function test_can_create_new_component_on_the_fly(): void
    {
        Livewire::test(MaterialPickerModal::class, [
            'showAllocationStep' => true,
            'designId' => $this->design->id,
            'alreadyAddedIds' => [],
        ])
            ->set('step', 'allocation')
            ->set('selectedMaterials', [
                [
                    'id' => 999,
                    'code' => 'TEST-001',
                    'name' => 'Test Material',
                    'category' => 'Test',
                    'uom' => 'pcs',
                    'component' => '',
                    'actual_consumption' => 1.0,
                    'notes' => '',
                ],
            ])
            ->call('createNewComponent', 'Reinforced Strap', 0)
            ->assertSet('selectedMaterials.0.component', 'Reinforced Strap');

        $this->assertDatabaseHas('components', [
            'company_id' => $this->company->id,
            'name' => 'Reinforced Strap',
        ]);
    }

    public function test_back_to_picker_preserves_selected_records(): void
    {
        $cat = MaterialCategory::create(['name' => 'Webbing', 'code' => 'WEB', 'company_id' => $this->company->id]);
        $sup = Supplier::create(['name' => 'Supplier W', 'code' => 'SUP-W', 'company_id' => $this->company->id]);
        $mat = Material::create([
            'code' => 'WEB-001',
            'name' => 'Nylon Webbing 25mm',
            'category' => 'webbing',
            'category_id' => $cat->id,
            'supplier_id' => $sup->id,
            'company_id' => $this->company->id,
            'uom' => 'meter',
        ]);

        Livewire::test(MaterialPickerModal::class, [
            'showAllocationStep' => true,
            'designId' => $this->design->id,
            'alreadyAddedIds' => [],
        ])
            ->call('singleSelect', $mat->id)
            ->assertSet('step', 'allocation')
            ->assertSet('selectedTableRecords', [(string) $mat->id])
            ->call('backToPicker')
            ->assertSet('step', 'picker')
            ->assertSet('selectedTableRecords', [(string) $mat->id]);
    }

    public function test_save_allocation_requires_actual_consumption_greater_than_zero(): void
    {
        Livewire::test(MaterialPickerModal::class, [
            'showAllocationStep' => true,
            'designId' => $this->design->id,
            'alreadyAddedIds' => [],
        ])
            ->set('step', 'allocation')
            ->set('selectedMaterials', [
                [
                    'id' => 999,
                    'code' => 'TEST-001',
                    'name' => 'Test Material',
                    'category' => 'Test',
                    'uom' => 'pcs',
                    'component' => '',
                    'actual_consumption' => null,
                    'notes' => '',
                ],
            ])
            ->call('saveAllocation')
            ->assertNotDispatched('close-modal');

        $this->assertDatabaseMissing('consumption_rates', [
            'material_id' => 999,
        ]);
    }

    public function test_removing_material_in_step_2_syncs_selected_table_records(): void
    {
        $cat = MaterialCategory::create(['name' => 'Fabric', 'code' => 'FAB', 'company_id' => $this->company->id]);
        $sup = Supplier::create(['name' => 'Supplier F', 'code' => 'SUP-F', 'company_id' => $this->company->id]);
        $mat1 = Material::create([
            'code' => 'FAB-001',
            'name' => 'Fabric A',
            'category' => 'fabric',
            'category_id' => $cat->id,
            'supplier_id' => $sup->id,
            'company_id' => $this->company->id,
            'uom' => 'yard',
        ]);
        $mat2 = Material::create([
            'code' => 'FAB-002',
            'name' => 'Fabric B',
            'category' => 'fabric',
            'category_id' => $cat->id,
            'supplier_id' => $sup->id,
            'company_id' => $this->company->id,
            'uom' => 'yard',
        ]);

        Livewire::test(MaterialPickerModal::class, [
            'showAllocationStep' => true,
            'designId' => $this->design->id,
            'alreadyAddedIds' => [],
        ])
            ->set('step', 'allocation')
            ->set('selectedMaterials', [
                [
                    'id' => $mat1->id,
                    'code' => $mat1->code,
                    'name' => $mat1->name,
                    'category' => 'Fabric',
                    'uom' => 'yard',
                    'component' => '',
                    'actual_consumption' => 1.0,
                    'notes' => '',
                ],
                [
                    'id' => $mat2->id,
                    'code' => $mat2->code,
                    'name' => $mat2->name,
                    'category' => 'Fabric',
                    'uom' => 'yard',
                    'component' => '',
                    'actual_consumption' => 1.0,
                    'notes' => '',
                ],
            ])
            ->set('selectedTableRecords', [(string) $mat1->id, (string) $mat2->id])
            ->call('removeSelectedMaterial', 0) // Remove mat1
            ->assertSet('selectedMaterials', [
                [
                    'id' => $mat2->id,
                    'code' => $mat2->code,
                    'name' => $mat2->name,
                    'category' => 'Fabric',
                    'uom' => 'yard',
                    'component' => '',
                    'actual_consumption' => 1.0,
                    'notes' => '',
                ],
            ])
            ->assertSet('selectedTableRecords', [(string) $mat2->id])
            ->call('backToPicker')
            ->assertSet('step', 'picker')
            ->assertSet('selectedTableRecords', [(string) $mat2->id]);
    }
}
