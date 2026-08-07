<?php

namespace Tests\Feature;

use App\Filament\Resources\HrCandidateResource;
use App\Filament\Resources\HrEmployeeResource\Pages\ListHrEmployees;
use App\Livewire\ManageCandidatesModal;
use App\Models\Company;
use App\Models\HrCandidate;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrPosition;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrEmployeeCandidateTest extends TestCase
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
            'name' => 'Acme Employee Candidate Corp',
            'code' => 'AECC',
            'address' => 'Jakarta',
        ]);

        CompanyContext::setCompany($this->company);
    }

    public function test_candidate_resource_navigation_is_hidden(): void
    {
        $this->assertFalse(HrCandidateResource::shouldRegisterNavigation());
    }

    public function test_list_employees_page_renders_with_candidates_action(): void
    {
        Livewire::test(ListHrEmployees::class)
            ->assertSuccessful()
            ->assertActionExists('candidates');
    }

    public function test_manage_candidates_modal_can_create_edit_and_delete_candidate(): void
    {
        Livewire::test(ManageCandidatesModal::class)
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'name' => 'John Doe Candidate',
                'nik' => '1234567890123456',
                'phone' => '081234567890',
                'email' => 'john@example.com',
                'status' => 'ready_to_hire',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('hr_candidates', [
            'company_id' => $this->company->id,
            'name' => 'John Doe Candidate',
            'nik' => '1234567890123456',
            'status' => 'ready_to_hire',
        ]);

        $candidate = HrCandidate::where('nik', '1234567890123456')->first();

        // Edit candidate
        Livewire::test(ManageCandidatesModal::class)
            ->callTableAction('edit', $candidate, data: [
                'name' => 'Johnathan Doe Candidate',
                'nik' => '1234567890123456',
                'status' => 'ready_to_hire',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('hr_candidates', [
            'id' => $candidate->id,
            'name' => 'Johnathan Doe Candidate',
        ]);

        // Delete candidate
        Livewire::test(ManageCandidatesModal::class)
            ->callTableAction('delete', $candidate)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('hr_candidates', [
            'id' => $candidate->id,
        ]);
    }

    public function test_manage_candidates_modal_can_hire_candidate(): void
    {
        $dept = HrDepartment::create([
            'company_id' => $this->company->id,
            'code' => 'ENG',
            'name' => 'Engineering',
            'is_active' => true,
        ]);

        $pos = HrPosition::create([
            'company_id' => $this->company->id,
            'code' => 'SE',
            'name' => 'Software Engineer',
            'department_id' => $dept->id,
            'is_active' => true,
        ]);

        $candidate = HrCandidate::create([
            'company_id' => $this->company->id,
            'name' => 'Jane Candidate',
            'nik' => '9876543210987654',
            'status' => 'ready_to_hire',
        ]);

        Livewire::test(ManageCandidatesModal::class)
            ->callTableAction('hire', $candidate, data: [
                'employee_number' => 'EMP-100',
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'join_date' => '2026-08-01',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertEquals('hired', $candidate->fresh()->status);
        $this->assertDatabaseHas('hr_employees', [
            'company_id' => $this->company->id,
            'candidate_id' => $candidate->id,
            'employee_number' => 'EMP-100',
            'name' => 'Jane Candidate',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
        ]);
    }

    public function test_manage_candidates_modal_can_manage_hiring_checklist(): void
    {
        $candidate = HrCandidate::create([
            'company_id' => $this->company->id,
            'name' => 'Bob Checklist Candidate',
            'nik' => '1122334455667788',
            'status' => 'screening',
        ]);

        Livewire::test(ManageCandidatesModal::class)
            ->callTableAction('checklist', $candidate, data: [
                'checklistItems' => [
                    [
                        'type' => 'mcu',
                        'label' => 'Medical Check Up',
                        'is_required' => true,
                        'status' => 'done',
                    ],
                    [
                        'type' => 'bank_account',
                        'label' => 'Bank BCA',
                        'is_required' => true,
                        'status' => 'pending',
                    ],
                ],
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('hr_hiring_checklist_items', [
            'candidate_id' => $candidate->id,
            'type' => 'mcu',
            'label' => 'Medical Check Up',
            'status' => 'done',
        ]);

        $this->assertDatabaseHas('hr_hiring_checklist_items', [
            'candidate_id' => $candidate->id,
            'type' => 'bank_account',
            'label' => 'Bank BCA',
            'status' => 'pending',
        ]);
    }
}
