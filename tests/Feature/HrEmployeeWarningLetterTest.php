<?php

namespace Tests\Feature;

use App\Filament\Resources\HrEmployeeResource\Pages\ListHrEmployees;
use App\Filament\Resources\HrWarningLetterResource;
use App\Livewire\ManageWarningLettersModal;
use App\Models\Company;
use App\Models\HrEmployee;
use App\Models\HrWarningLetter;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrEmployeeWarningLetterTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private HrEmployee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->company = Company::create([
            'name' => 'Acme Employee Warning Corp',
            'code' => 'AEWC',
            'address' => 'Jakarta',
        ]);

        CompanyContext::setCompany($this->company);

        $this->employee = HrEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-WL-01',
            'name' => 'Test Employee Warning',
            'nik' => '1234567890123499',
            'status' => 'active',
        ]);
    }

    public function test_warning_letter_resource_navigation_is_hidden(): void
    {
        $this->assertFalse(HrWarningLetterResource::shouldRegisterNavigation());
    }

    public function test_list_employees_page_renders_with_warning_letters_action(): void
    {
        Livewire::test(ListHrEmployees::class)
            ->assertSuccessful()
            ->assertActionExists('warning_letters');
    }

    public function test_manage_warning_letters_modal_can_create_edit_and_delete_warning_letter(): void
    {
        Livewire::test(ManageWarningLettersModal::class)
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'employee_id' => $this->employee->id,
                'level' => 'sp_1',
                'letter_number' => 'SP1/2026/001',
                'issued_date' => '2026-07-20',
                'reason' => 'Late arrival multiple times',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('hr_warning_letters', [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'level' => 'sp_1',
            'letter_number' => 'SP1/2026/001',
            'reason' => 'Late arrival multiple times',
        ]);

        $warningLetter = HrWarningLetter::where('letter_number', 'SP1/2026/001')->first();

        // Edit warning letter
        Livewire::test(ManageWarningLettersModal::class)
            ->callTableAction('edit', $warningLetter, data: [
                'employee_id' => $this->employee->id,
                'level' => 'sp_2',
                'letter_number' => 'SP1/2026/001',
                'issued_date' => '2026-07-20',
                'reason' => 'Updated reason: repeated late arrival',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('hr_warning_letters', [
            'id' => $warningLetter->id,
            'level' => 'sp_2',
            'reason' => 'Updated reason: repeated late arrival',
        ]);

        // Delete warning letter
        Livewire::test(ManageWarningLettersModal::class)
            ->callTableAction('delete', $warningLetter)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('hr_warning_letters', [
            'id' => $warningLetter->id,
        ]);
    }
}
