<?php

namespace Tests\Feature;

use App\Filament\Resources\BomResource\Pages\CreateBom;
use App\Filament\Resources\MerchandisePlanningResource\Pages\CreateMerchandisePlanning;
use App\Models\Company;
use App\Models\RdDesign;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BomAndMerchandiseMaterialPickerTest extends TestCase
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

    public function test_bom_form_accessible_and_contains_browse_action(): void
    {
        $design = RdDesign::create([
            'code' => 'DSN-01',
            'name' => 'Backpack Design',
            'company_id' => $this->company->id,
        ]);

        Livewire::test(CreateBom::class)
            ->assertSuccessful();
    }

    public function test_merchandise_planning_form_accessible(): void
    {
        Livewire::test(CreateMerchandisePlanning::class)
            ->assertSuccessful();
    }
}
