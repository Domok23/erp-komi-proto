<?php

namespace Tests\Feature;

use App\Filament\Resources\PoSupplierResource\Pages\CreatePoSupplier;
use App\Filament\Resources\SubconMaterialOutResource\Pages\CreateSubconMaterialOut;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PoAndSubconMaterialPickerTest extends TestCase
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

    public function test_po_supplier_create_form_renders_properly(): void
    {
        Livewire::test(CreatePoSupplier::class)
            ->assertSuccessful();
    }

    public function test_subcon_material_out_create_form_renders_properly(): void
    {
        Livewire::test(CreateSubconMaterialOut::class)
            ->assertSuccessful();
    }
}
