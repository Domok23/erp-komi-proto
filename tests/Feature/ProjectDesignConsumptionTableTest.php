<?php

namespace Tests\Feature;

use App\Livewire\ProjectDesignConsumptionTable;
use App\Models\Company;
use App\Models\ConsumptionRate;
use App\Models\Material;
use App\Models\RdDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectDesignConsumptionTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_empty_state_when_no_design_id(): void
    {
        Livewire::test(ProjectDesignConsumptionTable::class)
            ->call('loadTable')
            ->assertSee('No R&D Design Selected')
            ->assertSee('Select an Approved R&D Design above to load its material formula specifications.');
    }

    public function test_renders_consumption_rates_when_design_id_provided(): void
    {
        $company = Company::create([
            'name' => 'PT Komitrando Emporio',
            'code' => 'KOMI',
            'address' => 'Yogyakarta',
        ]);

        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DSN-BAG-001',
            'name' => 'Backpack Urban Alpha',
            'product_type' => 'Backpack',
            'status' => 'approved',
            'version' => '1.0',
        ]);

        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-CORD-100',
            'name' => 'Cordura 1000D Black',
            'category' => 'Fabric',
            'unit' => 'yard',
            'price' => 45000,
        ]);

        ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $material->id,
            'component' => 'Main Body',
            'standard_rate' => 1.7500,
            'unit' => 'yard',
            'wastage_rate' => 4.00,
        ]);

        Livewire::test(ProjectDesignConsumptionTable::class, ['designId' => $design->id])
            ->call('loadTable')
            ->assertSee('Main Body')
            ->assertSee('Cordura 1000D Black')
            ->assertSee('1.75')
            ->assertSee('yard')
            ->assertSee('4.00%')
            ->assertDontSee('No R&D Design Selected');
    }
}
