<?php

namespace Tests\Feature;

use App\Livewire\Components\MaterialPickerModal;
use App\Models\Company;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialPickerModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['code' => 'KOMI', 'name' => 'PT Komitrando']);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
        CompanyContext::setCompany($this->company);
    }

    public function test_renders_material_list_with_pagination(): void
    {
        Material::create([
            'code' => 'MAT-TEST-001',
            'name' => 'Nylon Oxford Fabric',
            'category' => 'fabric',
            'is_active' => true,
        ]);

        Livewire::test(MaterialPickerModal::class)
            ->assertSee('MAT-TEST-001')
            ->assertSee('Nylon Oxford Fabric');
    }

    public function test_filters_by_search_query(): void
    {
        Material::create(['code' => 'FAB-001', 'name' => 'Cotton Fabric', 'category' => 'fabric', 'is_active' => true]);
        Material::create(['code' => 'ZIP-001', 'name' => 'YKK Zipper', 'category' => 'zipper', 'is_active' => true]);

        Livewire::test(MaterialPickerModal::class)
            ->searchTable('Cotton')
            ->assertSee('FAB-001')
            ->assertDontSee('ZIP-001');
    }

    public function test_filters_by_supplier_context(): void
    {
        $supplierA = Supplier::create(['code' => 'SUP-A', 'name' => 'Supplier Alpha', 'company_id' => $this->company->id]);
        $supplierB = Supplier::create(['code' => 'SUP-B', 'name' => 'Supplier Beta', 'company_id' => $this->company->id]);

        Material::create(['code' => 'MAT-A', 'name' => 'Mat A', 'category' => 'fabric', 'supplier_id' => $supplierA->id, 'is_active' => true]);
        Material::create(['code' => 'MAT-B', 'name' => 'Mat B', 'category' => 'fabric', 'supplier_id' => $supplierB->id, 'is_active' => true]);

        Livewire::test(MaterialPickerModal::class, ['supplierId' => $supplierA->id])
            ->assertSee('MAT-A')
            ->assertDontSee('MAT-B');
    }

    public function test_filters_by_table_supplier_filter(): void
    {
        $supplierA = Supplier::create(['code' => 'SUP-A1', 'name' => 'Supplier Alpha One', 'company_id' => $this->company->id]);
        $supplierB = Supplier::create(['code' => 'SUP-B1', 'name' => 'Supplier Beta One', 'company_id' => $this->company->id]);

        Material::create(['code' => 'MAT-A1', 'name' => 'Mat A1', 'category' => 'fabric', 'supplier_id' => $supplierA->id, 'is_active' => true]);
        Material::create(['code' => 'MAT-B1', 'name' => 'Mat B1', 'category' => 'fabric', 'supplier_id' => $supplierB->id, 'is_active' => true]);

        Livewire::test(MaterialPickerModal::class)
            ->filterTable('supplier_id', $supplierA->id)
            ->assertSee('MAT-A1')
            ->assertDontSee('MAT-B1');
    }

    public function test_can_single_select_material(): void
    {
        $mat1 = Material::create(['code' => 'MAT-01', 'name' => 'Item 1', 'category' => 'fabric', 'is_active' => true]);

        Livewire::test(MaterialPickerModal::class)
            ->call('singleSelect', $mat1->id)
            ->assertDispatched('materials-picked');
    }

    public function test_can_bulk_add_selected_materials(): void
    {
        $mat1 = Material::create(['code' => 'MAT-01', 'name' => 'Item 1', 'category' => 'fabric', 'is_active' => true]);
        $mat2 = Material::create(['code' => 'MAT-02', 'name' => 'Item 2', 'category' => 'zipper', 'is_active' => true]);

        Livewire::test(MaterialPickerModal::class)
            ->callTableBulkAction('add_selected', [$mat1, $mat2])
            ->assertDispatched('materials-picked');
    }

    public function test_already_added_material_is_disabled_from_selection(): void
    {
        $mat1 = Material::create(['code' => 'MAT-01', 'name' => 'Item 1', 'category' => 'fabric', 'is_active' => true]);

        Livewire::test(MaterialPickerModal::class, ['alreadyAddedIds' => [$mat1->id]])
            ->assertTableActionDisabled('select', $mat1)
            ->assertSee('(Already in list)');
    }
}
