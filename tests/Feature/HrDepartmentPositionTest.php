<?php

namespace Tests\Feature;

use App\Filament\Resources\HrDepartmentResource;
use App\Filament\Resources\HrDepartmentResource\Pages\CreateHrDepartment;
use App\Filament\Resources\HrDepartmentResource\Pages\EditHrDepartment;
use App\Filament\Resources\HrDepartmentResource\Pages\ListHrDepartments;
use App\Filament\Resources\HrPositionResource;
use App\Livewire\ManagePositionsModal;
use App\Models\Company;
use App\Models\HrDepartment;
use App\Models\HrPosition;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrDepartmentPositionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->company = Company::create([
            'name' => 'Acme HR Corp',
            'code' => 'AHRC',
            'address' => 'Jakarta',
        ]);

        CompanyContext::setCompany($this->company);
    }

    public function test_position_resource_navigation_is_hidden(): void
    {
        $this->assertFalse(HrPositionResource::shouldRegisterNavigation());
    }

    public function test_list_departments_page_renders(): void
    {
        Livewire::test(ListHrDepartments::class)
            ->assertSuccessful()
            ->assertActionExists('positions');
    }

    public function test_manage_positions_modal_can_create_edit_and_delete_position(): void
    {
        $dept = HrDepartment::create([
            'company_id' => $this->company->id,
            'code' => 'IT',
            'name' => 'Information Technology',
            'is_active' => true,
        ]);

        // Render table
        Livewire::test(ManagePositionsModal::class)
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'code' => 'DEV',
                'name' => 'Developer',
                'department_id' => $dept->id,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('hr_positions', [
            'company_id' => $this->company->id,
            'code' => 'DEV',
            'name' => 'Developer',
            'department_id' => $dept->id,
        ]);

        $position = HrPosition::where('code', 'DEV')->first();

        // Edit position
        Livewire::test(ManagePositionsModal::class)
            ->callTableAction('edit', $position, data: [
                'code' => 'DEV-SR',
                'name' => 'Senior Developer',
                'department_id' => null,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('hr_positions', [
            'id' => $position->id,
            'code' => 'DEV-SR',
            'name' => 'Senior Developer',
            'department_id' => null,
        ]);

        // Delete position
        Livewire::test(ManagePositionsModal::class)
            ->callTableAction('delete', $position)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('hr_positions', [
            'id' => $position->id,
        ]);
    }

    public function test_create_department_with_multiple_orphan_positions(): void
    {
        $orphan1 = HrPosition::create([
            'company_id' => $this->company->id,
            'code' => 'P1',
            'name' => 'Position 1',
            'department_id' => null,
            'is_active' => true,
        ]);

        $orphan2 = HrPosition::create([
            'company_id' => $this->company->id,
            'code' => 'P2',
            'name' => 'Position 2',
            'department_id' => null,
            'is_active' => true,
        ]);

        Livewire::test(CreateHrDepartment::class)
            ->fillForm([
                'code' => 'HR',
                'name' => 'Human Resources',
                'is_active' => true,
                'positions' => [$orphan1->id, $orphan2->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $dept = HrDepartment::where('code', 'HR')->first();
        $this->assertNotNull($dept);

        $this->assertEquals($dept->id, $orphan1->fresh()->department_id);
        $this->assertEquals($dept->id, $orphan2->fresh()->department_id);
    }

    public function test_edit_department_attaches_and_detaches_positions(): void
    {
        $dept = HrDepartment::create([
            'company_id' => $this->company->id,
            'code' => 'FIN',
            'name' => 'Finance',
            'is_active' => true,
        ]);

        $pos1 = HrPosition::create([
            'company_id' => $this->company->id,
            'code' => 'ACC',
            'name' => 'Accountant',
            'department_id' => $dept->id,
            'is_active' => true,
        ]);

        $orphan = HrPosition::create([
            'company_id' => $this->company->id,
            'code' => 'AUD',
            'name' => 'Auditor',
            'department_id' => null,
            'is_active' => true,
        ]);

        // Detach ACC, attach AUD
        Livewire::test(EditHrDepartment::class, ['record' => $dept->getRouteKey()])
            ->fillForm([
                'positions' => [$orphan->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($pos1->fresh()->department_id);
        $this->assertEquals($dept->id, $orphan->fresh()->department_id);
    }
}
