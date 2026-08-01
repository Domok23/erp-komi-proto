<?php

namespace Tests\Feature;

use App\Filament\Resources\HrLeaveRequestResource\Pages\ListHrLeaveRequests;
use App\Filament\Resources\HrLeaveTypeResource;
use App\Livewire\ManageLeaveTypesModal;
use App\Models\Company;
use App\Models\HrLeaveType;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrLeaveTypeRequestTest extends TestCase
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
            'name' => 'Acme Leave Type Corp',
            'code' => 'ALTC',
            'address' => 'Jakarta',
        ]);

        CompanyContext::setCompany($this->company);
    }

    public function test_leave_type_resource_navigation_is_hidden(): void
    {
        $this->assertFalse(HrLeaveTypeResource::shouldRegisterNavigation());
    }

    public function test_list_leave_requests_page_renders_with_leave_types_action(): void
    {
        Livewire::test(ListHrLeaveRequests::class)
            ->assertSuccessful()
            ->assertActionExists('leave_types');
    }

    public function test_manage_leave_types_modal_can_create_edit_and_delete_leave_type(): void
    {
        Livewire::test(ManageLeaveTypesModal::class)
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'code' => 'ANNUAL',
                'name' => 'Annual Leave',
                'default_quota_days' => 12,
                'requires_document' => false,
                'is_sick_type' => false,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('hr_leave_types', [
            'company_id' => $this->company->id,
            'code' => 'ANNUAL',
            'name' => 'Annual Leave',
            'default_quota_days' => 12,
        ]);

        $leaveType = HrLeaveType::where('code', 'ANNUAL')->first();

        // Edit leave type
        Livewire::test(ManageLeaveTypesModal::class)
            ->callTableAction('edit', $leaveType, data: [
                'code' => 'ANNUAL',
                'name' => 'Updated Annual Leave',
                'default_quota_days' => 14,
                'requires_document' => false,
                'is_sick_type' => false,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('hr_leave_types', [
            'id' => $leaveType->id,
            'name' => 'Updated Annual Leave',
            'default_quota_days' => 14,
        ]);

        // Delete leave type
        Livewire::test(ManageLeaveTypesModal::class)
            ->callTableAction('delete', $leaveType)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('hr_leave_types', [
            'id' => $leaveType->id,
        ]);
    }
}
