<?php

namespace Tests\Feature;

use App\Filament\Resources\HrEmployeeResource\Pages\EditHrEmployee;
use App\Filament\Resources\HrEmployeeResource\RelationManagers\ContractsRelationManager;
use App\Models\Company;
use App\Models\HrEmployee;
use App\Models\HrEmploymentContract;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrEmployeeContractsRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_contract_to_active_ends_other_active_contracts(): void
    {
        $company = Company::create([
            'name' => 'Komi Test',
            'code' => 'KT',
            'address' => 'Jakarta',
        ]);

        CompanyContext::setCompany($company);

        $user = User::factory()->create(['company_id' => $company->id]);

        $employee = HrEmployee::create([
            'company_id' => $company->id,
            'employee_number' => 'EMP-1',
            'name' => 'Budi',
            'status' => 'active',
            'join_date' => '2026-07-01',
            'created_by' => $user->id,
        ]);

        $contractA = $employee->contracts()->create([
            'type' => 'pkwt',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $contractB = $employee->contracts()->create([
            'type' => 'pkwt',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'ended',
        ]);

        Livewire::test(ContractsRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass' => EditHrEmployee::class,
        ])->callTableAction('edit', $contractB, data: ['status' => 'active']);

        $this->assertSame('ended', $contractA->fresh()->status);
        $this->assertSame('active', $contractB->fresh()->status);
        $this->assertSame(
            1,
            HrEmploymentContract::where('employee_id', $employee->id)->where('status', 'active')->count()
        );
    }
}
